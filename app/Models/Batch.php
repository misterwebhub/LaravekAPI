<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\Uuids;

class Batch extends Model
{
    use HasFactory;
    use Uuids;
    use LogsActivity;

    public const TYPE_ALUMINI = 2;

    protected $hidden = ['pivot'];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Batch {$eventName}")
            ->useLogName('Batch')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'batch_subject');
    }

    /**
     * Get the centre .
     */
    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }

    public function phases()
    {
        return $this->belongsToMany(Phase::class, 'batch_phase')->select('id', 'name', 'start_date', 'end_date');
    }
}
