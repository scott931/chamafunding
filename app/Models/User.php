<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'date_of_birth',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'is_verified',
        'verification_token',
        'approval_status',
        'is_approved',
        'approved_at',
        'approved_by',
        'approval_notes',
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
            'date_of_birth' => 'date',
            'is_verified' => 'boolean',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    // Relationships
    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    public function contributions()
    {
        return $this->hasMany(CampaignContribution::class);
    }

    public function savingsAccounts()
    {
        return $this->hasMany(SavingsAccount::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function preferences()
    {
        return $this->hasMany(UserPreference::class);
    }

    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function savedCampaigns()
    {
        return $this->hasMany(SavedCampaign::class);
    }

    // Campaign assignment relationships
    public function assignedCampaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_users', 'user_id', 'campaign_id')
            ->withPivot('assigned_by', 'assigned_at', 'notes')
            ->withTimestamps();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if user is approved
     */
    public function isApproved(): bool
    {
        return $this->is_approved && $this->approval_status === 'approved';
    }

    /**
     * Check if user is pending approval
     */
    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    /**
     * Check if user is declined
     */
    public function isDeclined(): bool
    {
        return $this->approval_status === 'declined';
    }

    /**
     * Get all admin roles that should redirect to admin dashboard.
     *
     * @return array<string>
     */
    public static function getAdminRoles(): array
    {
        return [
            'Super Admin',
            'Financial Admin',
            'Moderator',
            'Support Agent',
            // Legacy roles (backward compatibility)
            'Treasurer',
            'Secretary',
            'Auditor',
        ];
    }

    /**
     * Check if user has any admin role.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(self::getAdminRoles());
    }
}
