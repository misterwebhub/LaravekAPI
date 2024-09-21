<?php

namespace App\Policies;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganisationPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function create(User $user)
    {
        if (
            $user->hasPermissionTo('centre.administrator') || $user->hasPermissionTo('organisation.administrator') ||
            $user->hasPermissionTo('program.administrator') || $user->hasPermissionTo('project.administrator')
        ) {
            return false;
        } else {
            return true;
        }
    }
    public function update(User $user, Organisation $organisation)
    {
        if ($user->hasPermissionTo('organisation.administrator')) {
            return $user->organisation_id == $organisation->id;
        } elseif ($user->hasPermissionTo('program.administrator')) {
            $organisationIds = $user->program->organisation->pluck('id')->toArray();
            return in_array($organisation->id, $organisationIds);
        } elseif (
            $user->hasPermissionTo('project.administrator')
        ) {
            $centreOrgIds = $user->project->centres->pluck('organisation_id')->toArray();
            return in_array($organisation->id, $centreOrgIds);
        } elseif (
            $user->hasPermissionTo('centre.administrator') || $user->hasRole('student') || $user->hasRole('alumni')
            || $user->hasRole('facilitator')
        ) {
            return false;
        } else {
            return true;
        }
    }
    public function view(User $user, Organisation $organisation)
    {
        return $this->update($user, $organisation);
    }
    public function delete(User $user, Organisation $organisation)
    {
        return $this->update($user, $organisation);
    }
}
