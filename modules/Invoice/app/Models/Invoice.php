<?php

namespace Modules\Invoice\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Contacts\Concerns\HasSource;
use Modules\Contacts\Models\Contact;
use Modules\Core\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Resource\Resourceable;
use Modules\Deals\Models\Deal;
use Modules\Invoice\Database\factories\InvoiceFactory;
use Modules\Invoice\Enums\InvoiceStatusEnum;
use Modules\Users\Models\User;

class Invoice extends Model
{
    use HasFactory,
        Resourceable,
        HasSource,
        SoftDeletes;

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->status = $model->status ?? InvoiceStatusEnum::UNPAID;
            $model->uuid = str()->uuid();
            $model->created_by = auth()->id();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_number',
        'uuid',
        'total',
        'total_tax',
        'date',
        'due_date',
        'status',
        'contact_id',
        'deal_id',
        'created_by',
        'taxable_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'status' => InvoiceStatusEnum::class
    ];

    protected function invoiceNumber(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => "INV-{$value}",
        );
    }

    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function getOnlinePaymentUrl(): string
    {
        return route('invoices.payment', $this->uuid);
    }
}
