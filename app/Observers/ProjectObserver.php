<?php

namespace App\Observers;

use App\Models\Project;

class ProjectObserver
{
    protected $userId;

    public function __construct()
    {
        $this->userId = optional(Project::getCurrentUser())->id;
    }
    /**
     * Handle the Project "creating" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function creating(Project $project)
    {
        $project->created_by = $this->userId;
    }

    /**
     * Handle the Project "updating" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function updating(Project $project)
    {
        $project->updated_by = $this->userId;
    }

    /**
     * Handle the Project "deleting" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function deleting(Project $project)
    {
        $project->deleted_by = $this->userId;
        $project->update();
    }
}
