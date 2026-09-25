<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Secure handling of KYC / application documents.
 *
 * Files are stored on the private `local` disk (storage/app/private) and are
 * only ever streamed back through an authorised controller - never through a
 * public URL.
 */
class DocumentService
{
    public const DISK = 'local';

    public function __construct(protected NotificationService $notifications)
    {
    }

    public function store(Model $owner, UploadedFile $file, array $attributes, User $actor): Document
    {
        $storedName = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        $folder = $this->folderFor($owner);
        $path = $file->storeAs($folder, $storedName, self::DISK);

        $document = Document::query()->create([
            'document_type_id' => $attributes['document_type_id'] ?? null,
            'documentable_type' => $owner->getMorphClass(),
            'documentable_id' => $owner->getKey(),
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'disk' => self::DISK,
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'status' => Document::STATUS_UPLOADED,
            'is_required' => (bool) ($attributes['is_required'] ?? false),
            'issued_number' => $attributes['issued_number'] ?? null,
            'expires_at' => $attributes['expires_at'] ?? null,
            'uploaded_by' => $actor->id,
        ]);

        $this->notifications->send(
            $this->reviewers($actor),
            'document-uploaded',
            'Document uploaded',
            sprintf('%s uploaded %s', $actor->name, $document->typeName()),
            ['url' => url()->previous(), 'actor_id' => $actor->id]
        );

        return $document;
    }

    public function replace(Document $document, UploadedFile $file, User $actor): Document
    {
        Storage::disk($document->disk)->delete($document->path);

        $storedName = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs($this->folderFor($document->documentable), $storedName, self::DISK);

        $document->update([
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'status' => Document::STATUS_UPLOADED,
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
            'rejection_reason' => null,
            'uploaded_by' => $actor->id,
        ]);

        return $document->refresh();
    }

    public function verify(Document $document, User $actor): Document
    {
        $document->update([
            'status' => Document::STATUS_VERIFIED,
            'is_verified' => true,
            'verified_by' => $actor->id,
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->notifications->send(
            [$document->uploader],
            'document-verified',
            'Document verified',
            sprintf('%s has been verified', $document->typeName()),
            ['url' => url()->previous(), 'actor_id' => $actor->id]
        );

        return $document->refresh();
    }

    public function reject(Document $document, User $actor, string $reason): Document
    {
        $document->update([
            'status' => Document::STATUS_REJECTED,
            'is_verified' => false,
            'verified_by' => $actor->id,
            'verified_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->notifications->send(
            [$document->uploader],
            'document-uploaded',
            'Document rejected',
            sprintf('%s was rejected: %s', $document->typeName(), $reason),
            ['url' => url()->previous(), 'actor_id' => $actor->id, 'severity' => 'error']
        );

        return $document->refresh();
    }

    public function delete(Document $document): void
    {
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }

    public function download(Document $document): StreamedResponse
    {
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404, 'File not found.');

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function stream(Document $document)
    {
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404, 'File not found.');

        return Storage::disk($document->disk)->response($document->path, $document->original_name);
    }

    protected function folderFor(?Model $owner): string
    {
        $type = $owner ? Str::snake(class_basename($owner)) : 'misc';

        return 'documents/'.$type.'/'.($owner?->getKey() ?? 0);
    }

    protected function reviewers(User $actor)
    {
        return User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', 'admin'))
            ->where('id', '!=', $actor->id)
            ->get();
    }
}
