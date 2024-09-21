<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
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
    public function update(User $user, Program $program)
    {
        if ($user->hasPermissionTo('program.administrator')) {
            return $user->program_id == $program->id;
        } elseif ($user->hasRole('student') || $user->hasRole('alumni') || $user->hasRole('facilitator')) {
            return false;
        } else {
            return true;
        }
    }
    public function view(User $user, Program $program)
    {
        return $this->update($user, $program);
    }
    public function delete(User $user, Program $program)
    {
        return $this->update($user, $program);
    }
}
