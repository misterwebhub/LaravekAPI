<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use App\Traits\Uuids;

class Report extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    protected $dates = ['created_at'];
    public const TYPE_FACILITATOR = 2;
    public const TYPE_STUDENT = 3;
    public const TYPE_ALUMINI = 4;
    public const TYPE_ADMIN = 1;
    public const TYPE_OPEN = 0;
    public const TYPE_CLOSED = 1;
    public const TYPE_REOPENED = 3;
    public const TYPE_PENDING = 2;
    public const TYPE_STATUS = 1;

    protected $touches = ['user'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Report {$eventName}")
            ->useLogName('Report')
            ->logOnlyDirty();
    }
    /**
     * Category of the Faq.
     */
    public function category()
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    /**
     * SubCategory of the Faq.
     */
    public function subCategory()
    {
        return $this->belongsTo(FaqSubCategory::class, 'faq_sub_category_id');
    }

    /**
     * Type of the Lesson.
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the profile of the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Subject of the Lesson.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
