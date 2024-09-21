<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Role;

class AccountTypeResource extends JsonResource
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
            'name' => $this->name,
            'is_admin' => ($this->superset_type == Role::ADMINTYPE ) ? Role::IS_ADMIN : Role::IS_NOT_ADMIN,
            'permissions' => $this->permissions,
        ];
    }
}
