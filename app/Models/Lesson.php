<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Lesson extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    public const ACTIVE_STATUS = 1;
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
        'tenant_id',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Lesson {$eventName}")
            ->useLogName('Lesson')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
    /**
     * Type of the Lesson.
     */
    public function lessonType()
    {
        return $this->belongsTo(LessonType::class);
    }

    /**
     * Category of the Lesson.
     */
    public function lessonCategory()
    {
        return $this->belongsTo(LessonCategory::class, 'lesson_category_id');
    }

    /**
     * Subject of the Lesson.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Course of the Lesson.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    /**
     * Course of the Lesson.
     */
    public function lessonLinks()
    {
        return $this->belongsToMany(Language::class, LanguageLesson::class)
            ->withPivot('folder_path', 'download_path', 'index_path', 'tenant_id', 'created_at', 'updated_at');
    }
}
