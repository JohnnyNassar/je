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
        'can_view_orders',
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
            'can_view_orders' => 'boolean',
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
     * Cost prices and profit margins. The owner always sees them; everyone
     * else, administrators included, needs the flag. It used to be a grant
     * that only mattered for staff, which meant an administrator could not be
     * refused cost without being demoted out of the whole back office.
     */
    public function canViewCost(): bool
    {
        return $this->isSuperAdmin() || (bool) $this->can_view_cost;
    }

    /**
     * Orders, revenue, and anything derived from them — the orders screen, the
     * dashboard money, a customer's order history and spend. Same shape as
     * canViewCost(): a capability an administrator can be refused while
     * keeping the rest of their job.
     */
    public function canViewOrders(): bool
    {
        return $this->isSuperAdmin() || (bool) $this->can_view_orders;
    }

    /**
     * Whether this account has had something taken away that its role would
     * normally include. Two administrators can now differ, so the role name
     * alone no longer describes what someone can reach.
     */
    public function isRestricted(): bool
    {
        return $this->restrictions() !== [];
    }

    /**
     * What has been withheld, in words, for a badge tooltip or an audit.
     *
     * @return array<int, string>
     */
    public function restrictions(): array
    {
        // Staff never had orders, so its absence is the role, not a removal.
        if (! $this->isAdmin()) {
            return [];
        }

        return array_values(array_filter([
            $this->canViewOrders() ? null : 'orders & revenue',
            $this->canViewCost() ? null : 'cost prices & profit',
        ]));
    }
}
