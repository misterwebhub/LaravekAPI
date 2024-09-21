<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Program extends AppModel
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    protected $hidden = [
        'created_by',
        'deleted_at',
        'created_at',
        'updated_at',
        'tenant_id',
        'status',
        'updated_by',
        'deleted_by'
    ];

    protected $fillable = [
        'name',
        'tenant_id'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Program {$eventName}")
            ->useLogName('Program')
            ->logOnlyDirty();
    }
    public function organisation()
    {
        return $this->belongsToMany(Organisation::class, 'organisation_program');
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
