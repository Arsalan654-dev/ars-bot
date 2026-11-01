<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'gateway_account_id',
        'label',
        'amount',
        'currency',
        'status',
        'reference',
        'gateway',
        'mode',
        'gateway_reference',
        'checkout_url',
        'paid_at',
        'expires_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PaymentGatewayAccount, $this>
     */
    public function gatewayAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentGatewayAccount::class, 'gateway_account_id');
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public static function generateReference(): string
    {
        return 'pay_'.Str::random(32);
    }
}
