<?php

namespace App\Observers;

use App\Models\Approval;

class ApprovalObserver
{
    protected $userId;

    public function __construct()
    {
        $this->userId = optional(Approval::getCurrentUser())->id;
    }
    /**
     * Handle the Approval "creating" event.
     *
     * @param  \App\Models\Approval  $approval
     * @return void
     */
    public function creating(Approval $approval)
    {
        $approval->created_by = $this->userId;
    }
}
