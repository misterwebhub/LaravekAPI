<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Create a new policy instance.
     *
     * @return void
     */

    public function update(User $user, Project $project)
    {
        if (
            $user->hasPermissionTo('project.administrator')
        ) {
            return $user->project_id == $project->id;
        } elseif ($user->hasPermissionTo('program.administrator')) {
            return $user->program_id == $project->program_id;
        } elseif ($user->hasRole('student') || $user->hasRole('alumni') || $user->hasRole('facilitator')) {
            return false;
        } else {
            return true;
        }
    }
    public function create(User $user, Project $project)
    {
        return  $this->update($user, $project);
    }
    public function view(User $user, Project $project)
    {
        return  $this->update($user, $project);
    }
    public function delete(User $user, Project $project)
    {
        return $this->update($user, $project);
    }
}
