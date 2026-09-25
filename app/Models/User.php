<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Auditable;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'role_id', 'designation', 'department_id', 'avatar_path',
        'status', 'password', 'last_login_at', 'last_login_ip', 'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    /* ------------------------------------------------------------------ relations */

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function extraPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')->withTimestamps();
    }

    public function createdLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'created_by');
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    /* ------------------------------------------------------------------ helpers */

    public function isAdmin(): bool
    {
        return (bool) $this->role?->isAdmin();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function permissionSlugs(): array
    {
        if ($this->isAdmin()) {
            return Permission::query()->pluck('slug')->all();
        }

        $roleSlugs = $this->role
            ? $this->role->permissions()->pluck('slug')->all()
            : [];

        $direct = $this->extraPermissions()->pluck('slug')->all();

        return array_values(array_unique([...$roleSlugs, ...$direct]));
    }

    public function hasPermissionTo(string $slug): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        static $cache = [];
        $key = $this->getKey();

        if (! isset($cache[$key])) {
            $cache[$key] = $this->permissionSlugs();
        }

        return in_array($slug, $cache[$key], true);
    }

    public function hasAnyPermission(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if ($this->hasPermissionTo($slug)) {
                return true;
            }
        }

        return false;
    }

    public function designationLabel(): string
    {
        return $this->employee?->designation?->name
            ?? $this->designation
            ?? $this->role?->name
            ?? 'Employee';
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar_path);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name));

        return strtoupper(substr($parts[0] ?? '', 0, 1).substr($parts[1] ?? '', 0, 1));
    }

    /** Employees see only records they created or that were assigned to them. */
    public function scopeOwnedRecords($query, string $createdColumn = 'created_by', string $assignedColumn = 'assigned_to')
    {
        if ($this->isAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($createdColumn, $assignedColumn) {
            $q->where($createdColumn, $this->id);
            if ($assignedColumn) {
                $q->orWhere($assignedColumn, $this->id);
            }
        });
    }
}
