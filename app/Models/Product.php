<?php

namespace App\Models;

use App\Traits\ProductScopes;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $image
 * @property string|null $barcode
 * @property string $sku
 * @property string|null $short_code
 * @property numeric $price
 * @property string|null $purchase_price
 * @property int|float $quantity
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $image_url
 * @method static Builder<static>|Product active()
 * @method static Builder<static>|Product bestSelling()
 * @method static Builder<static>|Product currentMonthBestSelling()
 * @method static \Database\Factories\ProductFactory factory($count = null, $state = [])
 * @method static Builder<static>|Product lowStock()
 * @method static Builder<static>|Product newModelQuery()
 * @method static Builder<static>|Product newQuery()
 * @method static Builder<static>|Product pastMonthsHotProducts()
 * @method static Builder<static>|Product query()
 * @method static Builder<static>|Product search($term)
 * @method static Builder<static>|Product whereBarcode($value)
 * @method static Builder<static>|Product whereCreatedAt($value)
 * @method static Builder<static>|Product whereDescription($value)
 * @method static Builder<static>|Product whereId($value)
 * @method static Builder<static>|Product whereImage($value)
 * @method static Builder<static>|Product whereName($value)
 * @method static Builder<static>|Product wherePrice($value)
 * @method static Builder<static>|Product wherePurchasePrice($value)
 * @method static Builder<static>|Product whereQuantity($value)
 * @method static Builder<static>|Product whereStatus($value)
 * @method static Builder<static>|Product whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Product extends Model
{
    use HasFactory;
    use ProductScopes;

    protected $fillable = [
        'name',
        'category_id',
        'description',
        'image',
        'barcode',
        'sku',
        'short_code',
        'price',
        'wholesale_price',
        'purchase_price',
        'quantity',
        'minimum_stock_level',
        'unit',
        'track_stock',
        'is_quick_item',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'minimum_stock_level' => 'decimal:2',
        'status' => 'boolean',
        'track_stock' => 'boolean',
        'is_quick_item' => 'boolean',
    ];

    protected $appends = ['image_url'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $product->barcode = static::cleanOptionalCode($product->barcode);
            $product->short_code = static::cleanOptionalCode($product->short_code);
            $product->sku = blank($product->sku) ? null : trim((string) $product->sku);
            $product->unit = blank($product->unit) ? 'piece' : trim((string) $product->unit);

            if (blank($product->sku)) {
                $product->sku = static::generateSku();
            }
        });
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public static function generateSku(string $prefix = 'SKU-'): string
    {
        $next = ((int) static::max('id')) + 1;

        do {
            $sku = $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (static::where('sku', $sku)->exists());

        return $sku;
    }

    public static function skuForId(int $id, string $prefix = 'SKU-'): string
    {
        return $prefix . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    public function ensureSkuFromId(): void
    {
        if (filled($this->sku)) {
            return;
        }

        $next = $this->id;
        do {
            $sku = static::skuForId($next);
            $next++;
        } while (static::where('sku', $sku)->where('id', '!=', $this->id)->exists());

        $this->forceFill(['sku' => $sku])->saveQuietly();
    }

    private static function cleanOptionalCode($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return trim((string) $value);
    }

    /**
     * Get the product image URL.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return Storage::disk('public')->url($this->image);
        }

        return asset('images/img-placeholder.jpg');
    }

    /**
     * Get the quantity as a friendly numeric value.
     */
    public function getQuantityAttribute($value): int|float
    {
        $quantity = (float) $value;

        return fmod($quantity, 1.0) === 0.0 ? (int) $quantity : $quantity;
    }
}
