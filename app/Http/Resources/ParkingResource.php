<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParkingResource extends JsonResource
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
            'check_in_time' => $this->check_in_time?->format('Y-m-d H:i:s'),
            'check_out_time' => $this->check_out_time?->format('Y-m-d H:i:s'),
            'check_in_image' => $this->check_in_image,
            'check_out_image' => $this->check_out_image,
            'status' => $this->status,
            'is_check_out_confirmed' => $this->is_check_out_confirmed,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
} 