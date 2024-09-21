<?php

namespace App\Observers;

use App\Models\Course;

class CourseObserver
{
    protected $userId;

    public function __construct()
    {
        $this->userId = optional(Course::getCurrentUser())->id;
    }
    /**
     * Handle the Course "creating" event.
     *
     * @param  \App\Models\Course  $course
     * @return void
     */
    public function creating(Course $course)
    {
        $course->created_by = $this->userId;
    }

    /**
     * Handle the Course "updating" event.
     *
     * @param  \App\Models\Course  $course
     * @return void
     */
    public function updating(Course $course)
    {
        $course->updated_by = $this->userId;
    }

    /**
     * Handle the Course "deleting" event.
     *
     * @param  \App\Models\Course  $course
     * @return void
     */
    public function deleting(Course $course)
    {
        $course->deleted_by = $this->userId;
        $course->update();
    }
}
