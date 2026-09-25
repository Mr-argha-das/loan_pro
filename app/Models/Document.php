<?php

namespace App\Models;

use App\Concerns\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Document extends Model
{
    use Filterable;
    use HasFactory;
    use SoftDeletes;
    protected $filterDateColumn = 'created_at';

    public const STATUS_PENDING = 'pending';
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'uuid', 'document_type_id', 'documentable_type', 'documentable_id', 'original_name',
        'stored_name', 'disk', 'path', 'mime_type', 'size', 'status', 'is_verified', 'is_required',
        'issued_number', 'expires_at', 'rejection_reason', 'verified_by', 'verified_at',
        'uploaded_by', 'meta',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_required' => 'boolean',
        'expires_at' => 'date',
        'verified_at' => 'datetime',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $document) {
            $document->uuid ??= (string) Str::uuid();
        });
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i === 0 ? 0 : 1).' '.$units[$i];
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_VERIFIED => 'success',
            self::STATUS_UPLOADED => 'info',
            self::STATUS_REJECTED => 'danger',
            default => 'warning',
        };
    }

    public function typeName(): string
    {
        return $this->documentType?->name ?? Str::headline(str_replace('_', ' ', (string) $this->original_name));
    }
}
