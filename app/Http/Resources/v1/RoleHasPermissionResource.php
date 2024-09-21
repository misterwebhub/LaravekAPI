<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleHasPermissionResource extends JsonResource
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
            'role_id' => $this['role_id'],
            'role_name' => $this['role_name'],
            'permission_id' => $this['permission_id'],
            'permission_name' => $this['permission_name'],
            'permission_title' => $this['permission_title'],
            'permission_group' => $this['permission_group'],
            'permission_parent_group' => $this['permission_parent_group'],
            'permission_sub_group' => $this['permission_sub_group'],
            'role_has_permission' => $this['role_has_permission'],
        ];
    }
}
