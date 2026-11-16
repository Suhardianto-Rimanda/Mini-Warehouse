<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMutationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'type'          => $this->type,
            'quantity'      => $this->quantity,
            'notes'         => $this->notes,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
            'product'       => new ProductResource($this->whenLoaded('product')),
            'user'          => new UserResource($this->whenLoaded('user')),
        ];
    }
}
