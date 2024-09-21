<?php

namespace App\Policies;

use App\Models\Batch;
use App\Models\Centre;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BatchPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $authUser, Batch $batch)
    {
        if ($authUser->hasPermissionTo('centre.administrator')) {
            return $authUser->centre_id == $batch->centre_id;
        } elseif ($authUser->hasPermissionTo('organisation.administrator')) {
            $centreIds = Centre::where('organisation_id', $authUser->organisation_id)->pluck('id')->toArray();
            return in_array($batch->centre_id, $centreIds);
        } elseif ($authUser->hasPermissionTo('program.administrator')) {
            $organisationIds = $authUser->program->organisation->pluck('id')->toArray();
            $centreIds = Centre::whereIn('organisation_id', $organisationIds)->pluck('id')->toArray();
            return in_array($batch->centre_id, $centreIds);
        } elseif (
            $authUser->hasPermissionTo('project.administrator')
        ) {
            $centreOrgIds = $authUser->project->centres->pluck('id')->toArray();
            return in_array($batch->centre_id, $centreOrgIds);
        } elseif ($authUser->hasRole('student') || $authUser->hasRole('alumni') || $authUser->hasRole('facilitator')) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $authUser, Batch $batch)
    {
        return  $this->view($authUser, $batch);
    }
}
