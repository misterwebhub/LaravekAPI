<?php

namespace App\Observers;

use App\Models\MqopsCentreVisit;

class MqopsCentreVisitObserver
{
    protected $userId;

    public function __construct()
    {
        $this->userId = optional(MqopsCentreVisit::getCurrentUser())->id;
    }
    /**
     * Handle the MqopsCentreVisit "creating" event.
     *
     * @param  \App\Models\MqopsCentreVisit  $MqopsCentreVisit
     * @return void
     */
    public function creating(MqopsCentreVisit $mqopsCentreVisit)
    {
        $mqopsCentreVisit->created_by = $this->userId;
    }

    /**
     * Handle the MqopsCentreVisit "updating" event.
     *
     * @param  \App\Models\MqopsExternalMeeting  $mqopsExternalMeeting
     * @return void
     */
    public function updating(MqopsCentreVisit $mqopsCentreVisit)
    {
        $mqopsCentreVisit->updated_by = $this->userId;
    }

    /**
     * Handle the MqopsCentreVisit "deleting" event.
     *
     * @param  \App\Models\MqopsCentreVisit  $mqopsCentreVisit
     * @return void
     */
    public function deleting(MqopsCentreVisit $mqopsCentreVisit)
    {
        $mqopsCentreVisit->deleted_by = $this->userId;
        $mqopsCentreVisit->update();
    }
}
