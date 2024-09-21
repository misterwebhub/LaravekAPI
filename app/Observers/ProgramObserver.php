<?php

namespace App\Observers;

use App\Models\Program;

class ProgramObserver
{
    protected $userId;

    public function __construct()
    {
        $this->userId = optional(Program::getCurrentUser())->id;
    }
    /**
     * Handle the Program "creating" event.
     *
     * @param  \App\Models\Program  $program
     * @return void
     */
    public function creating(Program $program)
    {
        $program->created_by =  $this->userId;
    }

    /**
     * Handle the Program "updating" event.
     *
     * @param  \App\Models\Program  $program
     * @return void
     */
    public function updating(Program $program)
    {
        $program->updated_by = $this->userId;
    }

    /**
     * Handle the Program "deleting" event.
     *
     * @param  \App\Models\Program  $program
     * @return void
     */
    public function deleting(Program $program)
    {
        $program->deleted_by = $this->userId;
        $program->update();
    }
}
