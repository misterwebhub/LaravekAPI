<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\Uuids;

class Placement extends Model
{
    use HasFactory;
    use Uuids;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Placement {$eventName}")
            ->useLogName('Placement')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
    /**
     * Get the placement course details of Student.
     */
    public function placementCourse()
    {
        return $this->belongsTo(PlacementCourse::class);
    }
    /**
     * Get the placement status details of Student.
     */
    public function placementStatus()
    {
        return $this->belongsTo(PlacementStatus::class);
    }
    /**
     * Get the placement type details of Student.
     */
    public function placementType()
    {
        return $this->belongsTo(PlacementType::class);
    }
    /**
     * Get the district details of Student.
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }
    /**
     * Get the offerletter status details of Student.
     */
    public function offerletterStatus()
    {
        return $this->belongsTo(OfferletterStatus::class);
    }
    /**
     * Get the offerletter type details of Student.
     */
    public function offerletterType()
    {
        return $this->belongsTo(OfferletterType::class);
    }
    /**
     * Get the centre details of Student.
     */
    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }
    /**
     * Get the sector details of Placement.
     */
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * Get the location details of Placement.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
