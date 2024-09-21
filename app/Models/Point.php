<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Point extends Model
{
    use HasFactory;
    use Uuids;
    use LogsActivity;

    protected $hidden = [
        'created_at',
        'updated_at',
        'tenant_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Point {$eventName}")
            ->useLogName('Point')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
}