<?php

namespace App\Observers;

use App\Models\MqopsInternalMeeting;

class MqopsInternalMeetingObserver
{
    protected $userId;

    public function __construct()
    {
        $this->userId = optional(MqopsInternalMeeting::getCurrentUser())->id;
    }

    /**
     * Handle the Internal "creating" event.
     *
     * @param  \App\Models\MqopsInternalMeeting $centre
     * @return void
     */
    public function creating(MqopsInternalMeeting $mqopsInternalMeeting)
    {
        $mqopsInternalMeeting->created_by = $this->userId;
    }

    /**
     * Handle the Internal "updating" event.
     *
     * @param  \App\Models\MqopsInternalMeeting  $internal
     * @return void
     */
    public function updating(MqopsInternalMeeting $mqopsInternalMeeting)
    {
        $mqopsInternalMeeting->updated_by = $this->userId;
    }

    /**
     * Handle the Internal "deleting" event.
     *
     * @param  \App\Models\MqopsInternalMeeting  $internal
     * @return void
     */
    public function deleting(MqopsInternalMeeting $mqopsInternalMeeting)
    {
        $mqopsInternalMeeting->deleted_by = $this->userId;
        $mqopsInternalMeeting->update();
    }
}
