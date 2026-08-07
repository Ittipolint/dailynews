<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    /**
     * All known menu keys. Used by the sidebar, the permission checkboxes in
     * the user form, and the EnsureMenuAccess middleware. Admin users always
     * have access to every menu regardless of the stored permissions array.
     */
    public const MENUS = [
        'dashboard',
        'news',
        'chat',
        'sources',
        'members',
        'categories',
        'credentials',
        'users',
    ];

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'permissions',
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
            'permissions' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * True when this user may open the given menu key. Admin always true;
     * everyone else must have the key listed in their permissions array.
     */
    public function canAccessMenu(string $menu): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($menu, (array) $this->permissions, true);
    }
}
