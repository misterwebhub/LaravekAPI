<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FacilitatorDetail extends Model
{
    use HasFactory;
    use Uuids;
    use LogsActivity;

    protected $dates = ['date_of_birth'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "FacilitatorDetail {$eventName}")
            ->useLogName('FacilitatorDetail')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }

    /**
     * Get the user details.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
