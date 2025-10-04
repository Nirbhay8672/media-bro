<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'mobile',
        'password',
        'subscription_start_date',
        'subscription_end_date',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'subscription_start_date' => 'date',
            'subscription_end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can manage users (only super admin)
     */
    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if user account is active
     */
    public function isAccountActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if user has active subscription
     */
    public function hasActiveSubscription(): bool
    {
        if ($this->isSuperAdmin()) {
            return true; // Super admin always has access
        }

        // Check if account is active first
        if (!$this->isAccountActive()) {
            return false;
        }

        if (!$this->subscription_start_date || !$this->subscription_end_date) {
            return false;
        }

        $now = now()->toDateString();
        return $now >= $this->subscription_start_date->toDateString() && 
               $now <= $this->subscription_end_date->toDateString();
    }

    /**
     * Check if subscription is expired
     */
    public function isSubscriptionExpired(): bool
    {
        if ($this->isSuperAdmin()) {
            return false;
        }

        if (!$this->subscription_end_date) {
            return true;
        }

        return now()->toDateString() > $this->subscription_end_date->toDateString();
    }

    /**
     * Get the templates created by this user
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    /**
     * Activate user account
     */
    public function activateAccount(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate user account
     */
    public function deactivateAccount(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Check and update account status based on subscription
     */
    public function updateAccountStatusBasedOnSubscription(): void
    {
        if ($this->isSuperAdmin()) {
            return; // Super admin accounts are always active
        }

        $isExpired = $this->isSubscriptionExpired();
        
        if ($isExpired && $this->is_active) {
            $this->deactivateAccount();
        } elseif (!$isExpired && !$this->is_active) {
            // Only auto-activate if subscription is valid
            $this->activateAccount();
        }
    }

    /**
     * Manually set subscription dates and update account status
     */
    public function setSubscriptionDates($startDate, $endDate): void
    {
        $this->update([
            'subscription_start_date' => $startDate,
            'subscription_end_date' => $endDate,
        ]);

        // Update account status based on new subscription dates
        $this->updateAccountStatusBasedOnSubscription();
    }
}
