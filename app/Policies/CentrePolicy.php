<?php

namespace App\Policies;

use App\Models\Centre;
use App\Models\User;

class CentrePolicy
{
    /**
     * Create a new policy instance.
     *
     * @return void
     */

    public function update(User $user, Centre $centre)
    {
        if ($user->hasPermissionTo('centre.administrator')) {
            return $user->centre_id == $centre->id;
        } elseif ($user->hasPermissionTo('organisation.administrator')) {
            return $user->organisation_id == $centre->organisation_id;
        } elseif ($user->hasPermissionTo('program.administrator')) {
            $organisationIds = $user->program->organisation->pluck('id')->toArray();
            return in_array($centre->organisation_id, $organisationIds);
        } elseif (
            $user->hasPermissionTo('project.administrator')
        ) {
            $centreOrgIds = $user->project->centres->pluck('id')->toArray();
            return in_array($centre->id, $centreOrgIds);
        } elseif ($user->hasRole('student') || $user->hasRole('alumni') || $user->hasRole('facilitator')) {
            return false;
        } else {
            return true;
        }
    }
    public function create(User $user, Centre $centre)
    {
        return  $this->update($user, $centre);
    }
    public function view(User $user, Centre $centre)
    {
        return $this->update($user, $centre);
    }
    public function delete(User $user, Centre $centre)
    {
        return $this->update($user, $centre);
    }
}
