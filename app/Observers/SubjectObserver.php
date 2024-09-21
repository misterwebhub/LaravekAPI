<?php

namespace App\Observers;

use App\Models\Subject;

class SubjectObserver
{
    protected $userId;

    public function __construct()
    {
        $this->userId = optional(Subject::getCurrentUser())->id;
    }
    /**
     * Handle the Subject "creating" event.
     *
     * @param  \App\Models\Subject  $subject
     * @return void
     */
    public function creating(Subject $subject)
    {
        $subject->created_by = $this->userId;
    }

    /**
     * Handle the Subject "updating" event.
     *
     * @param  \App\Models\Subject  $subject
     * @return void
     */
    public function updating(Subject $subject)
    {
        $subject->updated_by = $this->userId;
    }

    /**
     * Handle the Subject "deleting" event.
     *
     * @param  \App\Models\Subject  $subject
     * @return void
     */
    public function deleting(Subject $subject)
    {
        $subject->deleted_by = $this->userId;
        $subject->update();
    }
}
