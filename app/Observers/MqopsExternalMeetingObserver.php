<?php

namespace App\Observers;

use App\Models\MqopsExternalMeeting;

class MqopsExternalMeetingObserver
{
    protected $userId;

    public function __construct()
    {
        $this->userId = optional(MqopsExternalMeeting::getCurrentUser())->id;
    }
    /**
     * Handle the MqopsExternalMeeting "creating" event.
     *
     * @param  \App\Models\MqopsExternalMeeting  $MqopsExternalMeeting
     * @return void
     */
    public function creating(MqopsExternalMeeting $mqopsExternalMeeting)
    {
        $mqopsExternalMeeting->created_by = $this->userId;
    }

    /**
     * Handle the MqopsExternalMeeting "updating" event.
     *
     * @param  \App\Models\MqopsExternalMeeting  $mqopsExternalMeeting
     * @return void
     */
    public function updating(MqopsExternalMeeting $mqopsExternalMeeting)
    {
        $mqopsExternalMeeting->updated_by = $this->userId;
    }

    /**
     * Handle the MqopsExternalMeeting "deleting" event.
     *
     * @param  \App\Models\MqopsExternalMeeting  $mqopsExternalMeeting
     * @return void
     */
    public function deleting(MqopsExternalMeeting $mqopsExternalMeeting)
    {
        $mqopsExternalMeeting->deleted_by = $this->userId;
        $mqopsExternalMeeting->update();
    }
}
