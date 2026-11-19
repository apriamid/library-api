<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'book'        => BookResource::make($this->whenLoaded('book')),
            'borrowed_at' => $this->borrowed_at,
            'due_at'      => $this->due_at->toDateString(),
            'returned_at' => $this->returned_at,
            'status'      => $this->returned_at ? 'returned' : 'borrowed',
        ];
    }
}
