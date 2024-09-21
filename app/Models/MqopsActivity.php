<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MqopsActivity extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    protected $table = "mqops_activity_trackers";
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
        'tenant_id',
        'pivot'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "MqopsActivity {$eventName}")
            ->useLogName('MqopsActivity')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
    /**
     * Get the state details.
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the centre type details.
     */
    public function centreType()
    {
        return $this->belongsTo(CentreType::class);
    }

    /**
     * Get the activity medium details.
     */
    public function activityMedium()
    {
        return $this->belongsTo(MqopsActivityMedium::class, 'mqops_activity_medium_id');
    }

    /**
     * Get the activity type details.
     */
    public function activityType()
    {
        return $this->belongsTo(MqopsActivityType::class, 'mqops_activity_type_id');
    }

    /**
     * Get the activity document.
     */
    public function documents()
    {
        return $this->hasMany(MqopsDocument::class, 'parent_id')->select('file');
    }

    /**
     * Get assigned batches
     * @return [type]
     */
    public function batches()
    {
        return $this->belongsToMany(Batch::class, 'batch_mqops_activity_tracker', 'mqops_activity_tracker_id')
            ->select(['id', 'name']);
    }

    /**
     * Get assigned centres
     * @return [type]
     */
    public function centres()
    {
        return $this->belongsToMany(Centre::class, 'centre_mqops_activity_tracker', 'mqops_activity_tracker_id')
            ->select(['id', 'name']);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leader()
    {
        return $this->belongsTo(MqopsLeaderDesignation::class,'leader_type_id');  
    }
    
}
