<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property float $price
 * @property float $discount
 * @property float $quantity
 * @property int $order_id
 * @property int|null $product_id
 * @property string|null $custom_item_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\Product $product
 * @method static Builder<static>|OrderItem newModelQuery()
 * @method static Builder<static>|OrderItem newQuery()
 * @method static Builder<static>|OrderItem query()
 * @method static Builder<static>|OrderItem whereCreatedAt($value)
 * @method static Builder<static>|OrderItem whereId($value)
 * @method static Builder<static>|OrderItem whereOrderId($value)
 * @method static Builder<static>|OrderItem wherePrice($value)
 * @method static Builder<static>|OrderItem whereProductId($value)
 * @method static Builder<static>|OrderItem whereQuantity($value)
 * @method static Builder<static>|OrderItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class OrderItem extends Model
{
    protected $fillable = [
        'price',
        'discount',
        'quantity',
        'unit_cost',
        'returned_quantity',
        'product_id',
        'order_id',
        'custom_item_name',
    ];

    protected $casts = [
        'price' => 'float',
        'discount' => 'float',
        'quantity' => 'float',
        'unit_cost' => 'float',
        'returned_quantity' => 'float',
    ];

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Get subtotal for this item.
     */
    public function subtotal(): float
    {
        return $this->netSubtotal();
    }

    public function grossSubtotal(): float
    {
        return (float) $this->price;
    }

    public function discountAmount(): float
    {
        return min((float) $this->discount, $this->grossSubtotal());
    }

    public function netSubtotal(): float
    {
        return max($this->grossSubtotal() - $this->discountAmount(), 0);
    }

    /**
     * Get unit price.
     */
    public function unitPrice(): float
    {
        return $this->quantity > 0 ? $this->grossSubtotal() / $this->quantity : 0;
    }

    public function netUnitPrice(): float
    {
        return $this->quantity > 0 ? $this->netSubtotal() / $this->quantity : 0;
    }

    public function returnableQuantity(): float
    {
        return max((float) $this->quantity - (float) $this->returned_quantity, 0);
    }

    public function returnedAmount(): float
    {
        return $this->netUnitPrice() * (float) $this->returned_quantity;
    }

    public function grossReturnedAmount(): float
    {
        return $this->unitPrice() * (float) $this->returned_quantity;
    }

    public function returnedDiscountAmount(): float
    {
        if ((float) $this->quantity <= 0) {
            return 0;
        }

        return $this->discountAmount() * ((float) $this->returned_quantity / (float) $this->quantity);
    }

    public function netSales(): float
    {
        return max($this->netUnitPrice() * $this->netQuantity(), 0);
    }

    public function netQuantity(): float
    {
        return max((float) $this->quantity - (float) $this->returned_quantity, 0);
    }

    public function netCost(): float
    {
        return $this->netQuantity() * (float) $this->unit_cost;
    }
}
