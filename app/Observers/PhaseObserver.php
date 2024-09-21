<?php

namespace App\Observers;

use App\Models\Phase;

class PhaseObserver
{
    protected $userId;

    public function __construct()
    {
        $this->userId = optional(Phase::getCurrentUser())->id;
    }
    /**
     * Handle the Phase "creating" event.
     *
     * @param  \App\Models\Phase  $phase
     * @return void
     */
    public function creating(Phase $phase)
    {
        $phase->created_by = $this->userId;
    }

    /**
     * Handle the Phase "updating" event.
     *
     * @param  \App\Models\Phase  $phase
     * @return void
     */
    public function updating(Phase $phase)
    {
        $phase->updated_by = $this->userId;
    }

    /**
     * Handle the Phase "deleting" event.
     *
     * @param  \App\Models\Phase  $phase
     * @return void
     */
    public function deleting(Phase $phase)
    {
        $phase->deleted_by = $this->userId;
        $phase->update();
    }
}
