<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->hasRole(\App\Services\PermissionService::ROLE_ADMIN);
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff' || $this->hasRole(\App\Services\PermissionService::ROLE_STAFF);
    }

    public function isViewer(): bool
    {
        return $this->hasRole(\App\Services\PermissionService::ROLE_VIEWER);
    }
}