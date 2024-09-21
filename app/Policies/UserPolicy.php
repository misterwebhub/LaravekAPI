<?php

namespace App\Policies;

use App\Models\Centre;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function update($authUser, User $user)
    {
        if ($authUser->hasPermissionTo('centre.administrator')) {
            return $authUser->centre_id == $user->centre_id;
        } elseif ($authUser->hasPermissionTo('organisation.administrator')) {
            $centreIds = Centre::where('organisation_id', $authUser->organisation_id)->pluck('id')->toArray();
            return in_array($user->centre_id, $centreIds);
        } elseif ($authUser->hasPermissionTo('program.administrator')) {
            $organisationIds = $authUser->program->organisation->pluck('id')->toArray();
            return in_array($user->organisation_id, $organisationIds);
        } elseif (
            $authUser->hasPermissionTo('project.administrator')
        ) {
            $centreOrgIds = $authUser->project->centres->pluck('id')->toArray();
            return in_array($user->centre_id, $centreOrgIds);
        } elseif ($user->hasRole('student') || $user->hasRole('alumni') || $user->hasRole('facilitator')) {
            return false;
        } else {
            return true;
        }
    }
    public function view($authUser, User $user)
    {
        return $this->update($authUser, $user);
    }
    public function delete($authUser, User $user)
    {
        return $this->update($authUser, $user);
    }
}
