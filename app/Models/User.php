<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;
    use \App\Concerns\LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'can_view_cost',
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
            'can_view_cost' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Back-office access: both super admins (owner) and admins. Used to gate
     * everything except owner-only areas (see SuperAdminOnly). Staff are excluded.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin'], true);
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Cost prices & profit margins are visible to back-office admins (admin /
     * super_admin), plus any staff member explicitly granted the can_view_cost
     * flag. Lets the owner expose cost to one catalog person without giving them
     * the full admin tier.
     */
    public function canViewCost(): bool
    {
        return $this->isAdmin() || (bool) $this->can_view_cost;
    }
}
