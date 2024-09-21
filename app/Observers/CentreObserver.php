<?php

namespace App\Observers;

use App\Models\Centre;

class CentreObserver
{
    protected $userId;

    public function __construct()
    {
        $this->userId = optional(Centre::getCurrentUser())->id;
    }

    /**
     * Handle the Centre "creating" event.
     *
     * @param  \App\Models\Centre  $centre
     * @return void
     */
    public function creating(Centre $centre)
    {
        $centre->created_by = $this->userId;
    }

    /**
     * Handle the Centre "updating" event.
     *
     * @param  \App\Models\Centre  $centre
     * @return void
     */
    public function updating(Centre $centre)
    {
        $centre->updated_by = $this->userId;
    }

    /**
     * Handle the Centre "deleting" event.
     *
     * @param  \App\Models\Centre  $centre
     * @return void
     */
    public function deleting(Centre $centre)
    {
        $centre->deleted_by = $this->userId;
        $centre->update();
    }
}
