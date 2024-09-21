<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\Uuids;

class StudentDetail extends Model
{
    use HasFactory;
    use Uuids;
    use LogsActivity;

    protected $dates = ['date_of_birth'];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "StudentDetail {$eventName}")
            ->useLogName('StudentDetail')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
    /**
     * Get the batch details of Student.
     */
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the educational details of Student.
     */
    public function educationalQualification()
    {
        return $this->belongsTo(EducationalQualification::class);
    }

    /**
     * Get the placement status details of Student.
     */
    public function placementStatus()
    {
        return $this->belongsTo(PlacementStatus::class);
    }

    /**
     * Get the trade details of Student.
     */
    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
