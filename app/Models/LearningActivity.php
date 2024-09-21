<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningActivity extends Model
{
    use HasFactory;
    use Uuids;

    public $timestamps = true;
    protected $guarded = [];
    protected $casts = [
        'activity' => 'array',
    ];
    public const API_TYPE_LAUNCH = 'Launch';
    public const API_TYPE_END = 'End';
    public const API_TYPE_RESULT_SUBMIT = 'result_submit';
    public const API_TYPE_ACTIVITY = 'activity';
    public const TYPE_SUBJECT = 1;
    public const TYPE_COURSE = 2;
    public const LESSON_COMPLETED = 1;
    public const LESSON_NOT_COMPLETED = 1;

    /**
     * Get the Lesson.
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
