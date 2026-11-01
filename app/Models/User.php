<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_USER = 'user';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company',
        'phone',
        'is_paid',
        'paid_at',
        'subscription_plan',
        'subscription_days',
        'subscription_ends_at',
        'plan_label',
        'plan_amount',
        'plan_currency',
        'plan_days',
        'payment_gateway_account_id',
        'bot_active',
        'api_key',
        'avatar_path',
        'logo_path',
        'bot_name',
        'bot_color',
        'bot_theme',
        'knowledge_base',
        'about_content',
        'payment_reminder_sent',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'plan_amount' => 'decimal:2',
            'bot_active' => 'boolean',
            'payment_reminder_sent' => 'boolean',
        ];
    }

    /**
     * The merchant account assigned to this client (falls back to the
     * platform-wide active account when none is assigned).
     *
     * @return BelongsTo<PaymentGatewayAccount, $this>
     */
    public function gatewayAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentGatewayAccount::class, 'payment_gateway_account_id');
    }

    /**
     * The gateway that should be used for this client's payments.
     */
    public function preferredGateway(): ?PaymentGatewayAccount
    {
        return $this->gatewayAccount ?? PaymentGatewayAccount::getActive();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isPaid(): bool
    {
        return $this->is_paid;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->is_paid
            && ($this->subscription_ends_at === null || $this->subscription_ends_at->isFuture());
    }

    public function subscriptionExpired(): bool
    {
        return $this->is_paid
            && $this->subscription_ends_at !== null
            && $this->subscription_ends_at->isPast();
    }

    /**
     * Whether this user's bot may serve chat requests right now.
     */
    public function botIsServing(): bool
    {
        return $this->bot_active && $this->hasActiveSubscription();
    }

    /**
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<KnowledgeEntry, $this>
     */
    public function knowledgeEntries(): HasMany
    {
        return $this->hasMany(KnowledgeEntry::class);
    }

    public static function generateApiKey(): string
    {
        return 'lisa_'.Str::random(40);
    }

    /**
     * Unread clients count for the notification badge.
     */
    public function unreadClientsCount(): int
    {
        return $this->clients()->where('is_read', false)->count();
    }
}
