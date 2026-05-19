<?php

namespace Modules\Invoice\Models;

use Modules\Billable\Models\Product;
use Modules\Core\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'description',
        'quantity',
        'unit_price',
        'total',
        'taxable_amount',
        'tax_rate',
        'tax_total',
        'invoice_id',
        'product_id',
        'name',
        'taxable_amount',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
