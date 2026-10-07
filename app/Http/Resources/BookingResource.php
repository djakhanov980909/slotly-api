<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Booking */
class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'price' => $this->price,
            'service' => new ServiceResource($this->whenLoaded('service')),
            'specialist' => new SpecialistResource($this->whenLoaded('specialist')),
            // SpecialistResource отдаёт только id и name, для клиента этого достаточно
            'client' => new SpecialistResource($this->whenLoaded('client')),
        ];
    }
}
