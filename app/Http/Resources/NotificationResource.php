<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = unserialize($this->data['message']); // Deserialize data

        return [
            'id'        => $this->id,
            'notification_type'   => $this->notification_type, // Assuming notifiable_id stores user ID
            'message'   => $data,
            'read_at'   => $this->read_at ? $this->read_at->toDateTimeString() : null,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
