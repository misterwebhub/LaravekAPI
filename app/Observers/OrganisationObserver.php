<?php

namespace App\Observers;

use App\Models\Organisation;

class OrganisationObserver
{
    protected $userId;

    public function __construct()
    {
        $this->userId = optional(Organisation::getCurrentUser())->id;
    }
    /**
     * Handle the Organisation "creating" event.
     *
     * @param  \App\Models\Organisation  $organisation
     * @return void
     */
    public function creating(Organisation $organisation)
    {
        $organisation->created_by = $this->userId;
    }

    /**
     * Handle the Organisation "updating" event.
     *
     * @param  \App\Models\Organisation  $organisation
     * @return void
     */
    public function updating(Organisation $organisation)
    {
        $organisation->updated_by = $this->userId;
    }

    /**
     * Handle the Organisation "deleting" event.
     *
     * @param  \App\Models\Organisation  $organisation
     * @return void
     */
    public function deleting(Organisation $organisation)
    {
        $organisation->deleted_by = $this->userId;
        $organisation->update();
    }
}
