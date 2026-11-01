<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'client_id',
        'conversation_id',
        'number',
        'payment_reference',
        'items',
        'total',
        'currency',
        'status',
        'payment_status',
        'invoice_path',
        'invoice_image_path',
        'invoiced_at',
        'invoice_sent_at',
        'paid_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'invoiced_at' => 'datetime',
            'invoice_sent_at' => 'datetime',
            'paid_at' => 'datetime',
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
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function itemsList(): array
    {
        if ($this->items === null || $this->items === '') {
            return [];
        }

        $decoded = json_decode($this->items, true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function generateNumber(): string
    {
        return 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
    }

    public static function generatePaymentReference(): string
    {
        return 'ord_'.Str::random(32);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }
}
