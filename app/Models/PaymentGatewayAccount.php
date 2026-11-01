<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A merchant account (Stripe / PayPal / Bank) that receives payments.
 * Only ONE account can be active at a time platform-wide — activating
 * one automatically deactivates all others.
 */
class PaymentGatewayAccount extends Model
{
    use HasFactory;

    public const GATEWAY_STRIPE = 'stripe';

    public const GATEWAY_PAYPAL = 'paypal';

    public const GATEWAY_BANK = 'bank';

    public const GATEWAYS = [
        self::GATEWAY_STRIPE => 'Stripe',
        self::GATEWAY_PAYPAL => 'PayPal',
        self::GATEWAY_BANK => 'Bank account',
    ];

    public const MODE_SANDBOX = 'sandbox';

    public const MODE_LIVE = 'live';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'gateway',
        'name',
        'mode',
        'credentials',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<PaymentGatewayAccount>  $query
     * @return Builder<PaymentGatewayAccount>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function getActive(): ?self
    {
        return static::query()->active()->latest('id')->first();
    }

    /**
     * Make this account the single active one.
     */
    public function activate(): void
    {
        static::query()->where('id', '!=', $this->id)->update(['is_active' => false]);

        $this->update(['is_active' => true]);
    }

    public function getCred(string $key, ?string $default = null): ?string
    {
        $credentials = $this->credentials ?? [];

        return isset($credentials[$key]) && $credentials[$key] !== '' ? (string) $credentials[$key] : $default;
    }

    public function isSandbox(): bool
    {
        return $this->mode === self::MODE_SANDBOX;
    }

    public function label(): string
    {
        return (self::GATEWAYS[$this->gateway] ?? $this->gateway).' · '.ucfirst($this->mode);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'gateway_account_id');
    }
}
