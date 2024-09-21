<?php

namespace App\Repositories\v1;

use App\Models\Centre;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\BatchPhase;
use App\Models\Batch;
use App\Models\User;
use App\Models\PhaseUser;
use App\Models\Subject;
use App\Services\ProjectProgramCustomSort;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\Filter\ProjectToCentreCustomFilter;
use Spatie\QueryBuilder\AllowedFilter;
use App\Models\Phase;
use App\Models\CentrePhase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 *
 * @package App\Repositories
 */
class ProjectsToCentreRepository
{
    /**
     * List all projects corresponding to a centre
     *
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [type]
     */
    public function listProject($request, $centre, $user)
    {
        $projectQuery = $centre->projects();
        $centrephases = $this->listProjectCentrePhases($centre->id);
        $projects = QueryBuilder::for($projectQuery)
            ->with('program')
            ->when(
                $user->hasPermissionTo('project.administrator'),
                function ($query) use ($user) {
                    return $query->where('project_id', $user->project_id);
                }
            )
            ->when(
                $user->hasPermissionTo('program.administrator'),
                function ($query) use ($user) {
                    $organisationIds = $user->program->organisation->pluck('id')->toArray();
                    $centreIds = Centre::whereIn('organisation_id', $organisationIds)->pluck('id')->toArray();
                    return $query->whereIn('centre_id', $centreIds);
                }
            )
            ->allowedFilters([
                'name', 'program.name',
                AllowedFilter::custom('search_value', new ProjectToCentreCustomFilter())->ignore(null),
            ])
            ->allowedSorts(
                [
                    'name',
                    AllowedSort::custom('program.name', new ProjectProgramCustomSort())
                ]
            );
        if (isset($request['limit'])) {
            $projects =   $projects->paginate($request['limit'] ?? null);
        } else {
            $projects =   $projects->get();
        }
        foreach ($projects as $project) {
            $project->phasenames = $centrephases[$project->id] ?? '';
        }
        return $projects;
    }

    /**
     * Assign a project corresponding to a centre
     *
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [type]
     */
    public function assignProject($request, $centre)
    {

        $centre->projects()->syncWithoutDetaching($request['project']);
        $project = Project::where('id', $request['project'])->first();
        $subjects = $project->subjects->pluck('id');
        $centre->subjects()->syncWithoutDetaching($subjects);

        $batches = Batch::where('centre_id', $centre->id)->get();
        foreach ($batches as $batch) {
            $batch->subjects()->syncWithoutDetaching($subjects);
        }

        $centre = $this->setPhasesToCentre($request, $centre);

        foreach ($subjects as $subject) {
            $subjectOrder = Subject::where('id', $subject)->first();
            $centre->subjects()->updateExistingPivot($subject, array('order' => $subjectOrder->order));
        }

        return $centre;
    }

    /**
     * Assign a project corresponding to a centre
     *
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [type]
     */
    public function editProject($request, $centre)
    {
        $phases = $request['phase'];
        $oldPhases = CentrePhase::select(['centre_phase.phase_id'])->join('phase_project', 'centre_phase.phase_id', 'phase_project.phase_id')
        ->where('centre_phase.centre_id', $centre->id)
            ->where('phase_project.project_id', $request['project'])
            ->whereNull('centre_phase.deleted_at')
            ->get()->pluck('phase_id')->toArray();
        $removedPhases = array_diff($oldPhases, $request['phase']);
        $addedPhases = array_diff($request['phase'], $oldPhases);
        if ($removedPhases) {
            // $centre->phases()->detach($removedPhases);
            CentrePhase::whereIn('phase_id', $removedPhases)->where('centre_id', $centre->id)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
            $centreBatches = Batch::where('centre_id', $centre->id)->whereNull('deleted_at')->get()->pluck('id')->toArray();
            BatchPhase::whereIn('phase_id', $removedPhases)->whereNull('deleted_at')->whereIn('batch_id', $centreBatches)->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
            if ($centre->auto_update_phase == Centre::ACTIVE_STATUS) {
                PhaseUser::whereIn('phase_id', $removedPhases)->where('centre_id', $centre->id)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
            }
        }
        if ($addedPhases) {
            $centre->phases()->attach($addedPhases);
            $users = $centre->users;
            $phaseRes = Phase::whereIn('id', $addedPhases)->get();
            foreach ($phaseRes as $selectPhase) {
                $usersnew = User::join('student_details', 'users.id', 'student_details.user_id')
                ->where('users.created_at', '>=', Carbon::parse($selectPhase->start_date)->format('Y-m-d 00:00:00'))
                ->where('users.created_at', '<=', Carbon::parse($selectPhase->end_date)->format('Y-m-d 23:59:59'))
                ->where('users.deleted_at', null)
                ->whereIn('users.type', [User::TYPE_STUDENT, User::TYPE_ALUMNI])
                ->where('users.centre_id', $centre->id)
                ->whereNull('student_details.batch_id')
                ->get()->pluck('user_id');
                PhaseUser::where('phase_id', $selectPhase->id)->whereIn('user_id', $usersnew)->delete();
                $selectPhase->users()->attach($usersnew, ['centre_id' => $centre->id]);

                $usersnew1 = User::join('facilitator_details', 'users.id', 'facilitator_details.user_id')
                ->where('users.created_at', '>=', Carbon::parse($selectPhase->start_date)->format('Y-m-d 00:00:00'))
                ->where('users.created_at', '<=', Carbon::parse($selectPhase->end_date)->format('Y-m-d 23:59:59'))
                ->where('users.deleted_at', null)
                ->where('users.type', User::TYPE_FACILITATOR)
                ->where('users.centre_id', $centre->id)
                ->whereNull('facilitator_details.batch_id')
                ->get()->pluck('user_id');
                PhaseUser::where('phase_id', $selectPhase->id)->whereIn('user_id', $usersnew1)->delete();
                $selectPhase->users()->attach($usersnew1, ['centre_id' => $centre->id]);
            }
        }
        return $centre;
    }



    /**
     * Set Phases data to centre
     * @param mixed $request
     * @return [collection]
     */
    private function setPhasesToCentre($request, $centre)
    {
        $phases = array_filter($request['phases']);
        if (!empty($phases)) {
            // $centre->phases()->syncWithoutDetaching($phases);

            $project = Project::find($request['project']);
            $oldPhases = $project->phases()->pluck('id')->toArray();
            $removedPhases = array_diff($oldPhases, $request['phases']);
            if ($removedPhases) {
                CentrePhase::where('centre_id', $centre->id)->whereIn('phase_id', $removedPhases)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
                $batches = $centre->batches()->pluck('id')->toArray();
                BatchPhase::whereIn('batch_id', $batches)->whereIn('phase_id', $removedPhases)->delete();
            }
            CentrePhase::where('centre_id', $centre->id)->whereIn('phase_id', $phases)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
            $centre->phases()->attach($phases);

            foreach ($phases as $phaseId) {
                $phase = Phase::where('id', $phaseId)->first();
                $users = User::join('student_details', 'users.id', 'student_details.user_id')
                    ->where('users.created_at', '>=', Carbon::parse($phase->start_date)->format('Y-m-d 00:00:00'))
                    ->where('users.created_at', '<=', Carbon::parse($phase->end_date)->format('Y-m-d 23:59:59'))
                    ->where('users.deleted_at', null)
                    ->whereIn('users.type', [User::TYPE_STUDENT, User::TYPE_ALUMNI])
                    ->where('users.centre_id', $centre->id)
                    ->whereNull('student_details.batch_id')
                    ->get();
                $usersfac = User::join('facilitator_details', 'users.id', 'facilitator_details.user_id')
                ->where('users.created_at', '>=', Carbon::parse($phase->start_date)->format('Y-m-d 00:00:00'))
                    ->where('users.created_at', '<=', Carbon::parse($phase->end_date)->format('Y-m-d 23:59:59'))
                    ->where('users.deleted_at', null)
                    ->where('users.type', User::TYPE_FACILITATOR)
                    ->where('users.centre_id', $centre->id)
                    ->whereNull('facilitator_details.batch_id')
                    ->get();
                $subjects = $phase->subjects()->pluck('id');
                $centre->subjects()->syncWithoutDetaching($subjects);
                if (!empty($users)) {
                    $phase->users()->attach($users->pluck('user_id'), ['centre_id' => $centre->id]);
                }
                if (!empty($usersfac)) {
                    $phase->users()->attach($usersfac->pluck('user_id'), ['centre_id' => $centre->id]);
                }
            }
        }
        return $centre;
    }

    /**
     * Delete project corresponding to a centre
     *
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [type]
     */
    public function deleteProject($request, $centre)
    {
        $centre->projects()->detach($request['project']);
        $project = Project::where('id', $request['project'])->first();
        $phases = $project->phases->pluck('id')->toArray();
        $batches = $centre->batches->pluck('id')->toArray();
        CentrePhase::where('centre_id', $centre->id)->whereIn('phase_id', $phases)->delete();
        BatchPhase::whereIn('batch_id', $batches)->whereIn('phase_id', $phases)->delete();
        if ($centre->auto_update_phase == Centre::ACTIVE_STATUS) {
            // $centre->phases()->detach($phases);
            foreach ($phases as $phase) {
                $users = $centre->users->pluck('id')->toArray();
                if ($users) {
                    // $phase->users()->detach($users);
                    PhaseUser::where('phase_id', $phase)->whereIn('user_id', $users)->delete();
                }
            }
        }
        
        return $centre;
    }

    /**
     * List all centres corresponding to a project
     *
     * @param mixed $request
     * @param mixed $project
     *
     * @return [type]
     */
    public function listCentre($request, $project, $user)
    {
        $centreQuery = $project->centres();
        $centres = QueryBuilder::for($centreQuery)
            ->when(
                $user->hasPermissionTo('centre.administrator'),
                function ($query) use ($user) {
                    return $query->where('centre_id', $user->centre_id);
                }
            )
            ->when(
                $user->hasPermissionTo('organisation.administrator'),
                function ($query) use ($user) {
                    $centreIds = $user->organisation->centres->pluck('id')->toArray();
                    return $query->whereIn('centre_id', $centreIds);
                }
            )->latest();
        $totalCount = $centres->count();
        $centres = $centres->allowedFilters([
            'name',
        ]);
        if (isset($request['limit'])) {
            $centres =   $centres->paginate($request['limit'] ?? null);
        } else {
            $centres =  $centres->get();
        }
        return ['centres' => $centres, 'total_count' => $totalCount];
    }

    /**
     * List all project-head corresponding to a project
     *
     * @param mixed $request
     * @param mixed $project
     *
     * @return [type]
     */
    public function listProjectHead($request, $project)
    {
        $projectHead = QueryBuilder::for(User::class)
            ->with('roles')
            ->role('project-head')
            ->where('users.tenant_id', getTenant())
            ->where('type', User::TYPE_ADMIN)
            ->where('project_id', $project->id)
            ->latest();
        $totalCount = $projectHead->count();
        $projectHead = $projectHead
            ->allowedFilters(['name']);
        if (isset($request['limit'])) {
            $projectHead = $projectHead->paginate($request['limit'] ?? null);
        } else {
            $projectHead = $projectHead->get();
        }
        return ['projectHead' => $projectHead, 'total_count' => $totalCount];
    }

    /**
     * List all phases corresponding to a project
     *
     * @param mixed $request
     * @param mixed $project
     *
     * @return [type]
     */
    public function listPhase($request, $project, $user)
    {
        $phaseQuery = $project->phases();
        $phases = QueryBuilder::for($phaseQuery)
            ->latest();
        $totalCount = $phases->count();
        $phases = $phases->allowedFilters([
            'name',
        ]);
        if (isset($request['limit'])) {
            $phases =   $phases->paginate($request['limit'] ?? null);
        } else {
            $phases =  $phases->get();
        }
        return ['phases' => $phases, 'total_count' => $totalCount];
    }

    /**
     * List all phases corresponding to a project centre
     *
     * @param mixed $request
     * @param mixed $project
     *
     * @return [type]
     */
    public function listProjectPhase($request, $centre)
    {
        $projectPhase = ProjectPhase::where('project_id', $request['project'])->get()->pluck('phase_id')->toArray();
        $phases = DB::table('centre_phase')->whereNull('deleted_at')->whereIn('phase_id', $projectPhase)
            ->where('centre_id', $centre->id)->get()->pluck('phase_id')->toArray();
        $phasesDet = Phase::whereIn('id', $phases)->get();
        return ['phases' => $phasesDet];
    }

    /**
     * List all phases corresponding to a project centre
     *
     * @param mixed $request
     * @param mixed $project
     *
     * @return [type]
     */
    public function listProjectCentrePhases($centre)
    {
        $phases = DB::table('centre_project')
                    ->selectRaw('centre_project.project_id,phases.name')->join('phase_project','centre_project.project_id','phase_project.project_id')
                    ->join('centre_phase', 'centre_phase.phase_id','phase_project.phase_id')
                    ->join('phases','centre_phase.phase_id','phases.id')
                    ->whereNull('centre_phase.deleted_at')
                    ->where('centre_phase.centre_id', $centre)
                    ->distinct()
                    ->get();
        $data = [];
        foreach ($phases as $phase) {
            if(isset($data[$phase->project_id])){
                $data[$phase->project_id] .= $phase->name . ',';
            } else {
                $data[$phase->project_id] = $phase->name . ',';
            }
        }
        return $data;
    }
}
