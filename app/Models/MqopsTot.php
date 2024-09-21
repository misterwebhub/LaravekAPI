<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MqopsTot extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    //use LogsActivity;

    protected $table = "mqops_tot_summary";
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
            ->setDescriptionForEvent(fn (string $eventName) => "MqopsTot {$eventName}")
            ->useLogName('MqopsTot')
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
     * Get the tot medium details.
     */
    public function totMedium()
    {
        return $this->belongsTo(MqopsActivityMedium::class, 'mqops_activity_medium_id');
    }

    /**
     * Get the tot type details.
     */
    public function totType()
    {
        return $this->belongsTo(MqopsTotType::class, 'tot_id');
    }

    /**
     * Get the tot document.
     */
    public function documents()
    {
        return $this->hasMany(MqopsDocument::class, 'parent_id')->select('file');
    }

    /**
     * Get the tot details.
     */
    public function details()
    {
        return $this->hasMany(MqopsTotSummaryProject::class, 'tot_summary_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
