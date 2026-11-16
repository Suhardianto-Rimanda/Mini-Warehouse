<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'sku'           => $this->sku,
            'name'          => $this->name,
            'unit'          => $this->unit,
            'stock'         => $this->stock,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
            'category'      => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
