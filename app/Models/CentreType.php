<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentreType extends Model
{
    use HasFactory, Uuids;

    protected $hidden = [
        'created_by',
        'created_at',
        'updated_at',
        'tenant_id'
    ];

    /**
     * Get the centres under cetre type.
     */
    public function centres()
    {
        return $this->hasMany(Centre::class);
    }
}
