<?php

namespace Modules\Invoice\DTO;

use Illuminate\Support\Collection;
use Modules\Billable\Models\Product;

class InvoiceItemDto {

    public function __construct(
        public ?string $productId,
        public ?string $description,
        public string $name,
        public float $quantity,
        public float $unitPrice,
        public float $total,
        public float $taxableAmount,
        public float $taxRate,
        public float $taxTotal
    )
    {}

    public static function fromProduct(Product $product, int $quantity = 1): self
    {
        $taxAmountPerUnit = ($product->tax_rate > 0)? ($product->unit_price * $product->tax_rate) / 100 : 0;

        $productId = $product->id;
        $description = $product->description;
        $name = $product->name;
        $unitPrice = $product->unit_price + $taxAmountPerUnit ;
        $total = $unitPrice * $quantity;
        $taxableAmount = $product->unit_price * $quantity;
        $taxRate = $product->tax_rate;
        $taxTotal = $taxAmountPerUnit * $quantity;
        return new self(
            $productId,
            $description,
            $name,
            $quantity,
            $unitPrice,
            $total,
            $taxableAmount,
            $taxRate,
            $taxTotal
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['product_id'] ?? null,
            $data['description'] ?? null,
            $data['name'],
            $data['quantity'] ?? 1,
            $data['unit_price'] ?? $data['total'] / ($data['quantity'] ?? 1),
            $data['total'],
            $data['taxable_amount'] ?? $data['total'],
            $data['tax_rate'] ?? 0,
            $data['tax_total'] ?? 0
        );
    }

    public static function fromProducts(Collection $products, int $quantity = 1): Collection
    {
        $items = collect();

        foreach ($products as $product) {
            $items->push(self::fromProduct($product, $quantity));
        }

        return $items;
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'description' => $this->description,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'total' => $this->total,
            'taxable_amount' => $this->taxableAmount,
            'tax_rate' => $this->taxRate,
            'tax_total' => $this->taxTotal,
        ];
    }
}
