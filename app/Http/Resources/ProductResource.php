<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read string|null $image
 * @property-read string $sku
 * @property-read string|null $unit
 * @property-read float $price
 * @property-read float|null $purchase_price
 * @property-read float|null $wholesale_price
 * @property-read float|null $minimum_stock_level
 * @property-read int $quantity
 * @property-read bool $status
 * @property-read \Illuminate\Support\Carbon $created_at
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'sku' => $this->sku,
            'unit' => $this->unit,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category'),
            'purchase_price' => $this->purchase_price,
            'price' => $this->price,
            'wholesale_price' => $this->wholesale_price,
            'minimum_stock_level' => $this->minimum_stock_level,
            'quantity' => $this->quantity,
            'track_stock' => $this->track_stock,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'image_url' => $this->image_url,
        ];
    }
}
