<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationContentResource extends JsonResource
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
            'notification_comment' => $this->comment,
            'tenant' => $this->tenant_id,
            'notification_content' => $this->content,
            'notification_key' => $this->key,
            'notification_send_type' => $this->send_type ?? null,
        ];
    }
}
