<?php

namespace App\Repositories\v1;

use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Project;
use App\Models\Phase;
use App\Models\PhaseUser;
use App\Models\Centre;
use Illuminate\Support\Facades\DB;
use App\Services\ProjectProgramCustomSort;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\Filter\ProjectCustomFilter;
use Spatie\QueryBuilder\AllowedFilter;

use Carbon\Carbon;
/**
 * [Description ProgramRepository]
 */
class ProjectRepository
{
    /**
     * List all projects
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request, $user)
    {
        $projects = QueryBuilder::for(Project::class)
            ->where('tenant_id', getTenant())
            ->with('program')
            ->when(
                $user->hasPermissionTo('project.administrator'),
                function ($query) use ($user) {
                    return $query->where('id', $user->project_id);
                }
            )
            ->when($user->hasPermissionTo('program.administrator'), function ($query) use ($user) {
                return $query->where('program_id', $user->program_id);
            });
        $totalCount = $projects->get()->count();
        $projects = $projects
            ->allowedFilters([
                'name', 'status', 'program.name',
                AllowedFilter::custom('search_value', new ProjectCustomFilter()),
            ])
            ->allowedSorts(
                [
                    'name', 'status',
                    AllowedSort::custom('program.name', new ProjectProgramCustomSort()),
                ]
            )
            ->latest()
            ->paginate($request['limit'] ?? null);
        return ['projects' => $projects, 'total_count' => $totalCount];
    }

    /**
     * Create a new Program
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request)
    {
        $project = new Project();
        $project = $this->setProject($request, $project);
        $project->save();
        $this->setPhases($request, $project);
        return $project;
    }

    /**
     * Delete a Project
     * @param mixed $project
     *
     * @return [type]
     */
    public function destroy($project)
    {
        $this->removePhaseUsers($project);
        $project->subjects()->detach();
        $project->centres()->detach();
        $project->phases()->detach();
        $project->delete();
    }

    /**
     * Update Project
     * @param mixed $request
     * @param mixed $project
     *
     * @return [json]
     */
    public function update($request, $project)
    {
        $project = $this->setProject($request, $project);
        $project->update();
        $oldPhases = $project->phases()->pluck('id')->toArray();
        $removedPhases = array_diff($oldPhases, $request['phase']);
        $addedPhases = array_diff($request['phase'], $oldPhases);
        $this->setPhases($request, $project);
        $this->setAddedPhaseUsers($addedPhases);
        $this->setRemovedPhaseUsers($removedPhases);
        return $project;
    }

    /**
     * Update status of project
     * @param mixed $request
     * @param mixed $project
     *
     * @return [type]
     */
    public function updateStatus($request, $project)
    {
        $project->status = $request['status'];
        $project->update();
        return $project;
    }

    /**
     * Set Project Data
     * @param mixed $request
     * @param mixed $project
     *
     * @return [collection]
     */
    private function setProject($request, $project)
    {
        $project->name = $request['name'];
        $project->tenant_id = getTenant();
        $project->program_id = $request['program'];
        return $project;
    }

    /**
     * Set Phase Data
     * @param mixed $request
     * @param mixed $project
     *
     * @return [collection]
     */
    private function setPhases($request, $project)
    {
        $phases = $request['phase'] ?? null;
        $project->phases()->detach();
        $project->phases()->attach($phases);
        return $project;
    }

    /**
     * Get unassigned Phases
     * @param mixed $project
     *
     * @return [collection]
     */
    public function unAssignedPhase($project)
    {
        $projectId = $project->id ?? null;
        $phases = Phase::select(DB::raw('id, name, phase_project.project_id'))
            ->leftJoin('phase_project', function ($join) use ($projectId) {
                $join->on('phases.id', '=', 'phase_project.phase_id');
            })
            ->where('phase_project.project_id', null)
            ->when($projectId, function ($q) use ($projectId) {
                return $q->orWhere('phase_project.project_id', '=', $projectId);
            })
            ->orderBy('phase_project.project_id', 'desc')
            ->orderBy('phases.name', 'asc')->get();
        return $phases;
    }

    public function setPhaseUsers($project, $removedPhases = [])
    {
        $phases = $project->phases;
        foreach ($phases as $phase) {
            $centres = $phase->centres;
            if ($centres) {
                foreach ($centres as $centre) {
                    $users = $centre->users;
                    $usersnew = $users->where('created_at', '>=', Carbon::parse($phase->start_date)->format('Y-m-d 00:00:00'))
                    ->where('created_at', '<=', Carbon::parse($phase->end_date)->format('Y-m-d 23:59:59'))->where('deleted_at', null);
                    $phase->users()->detach($usersnew);
                    $phase->users()->attach($usersnew, ['centre_id' => $centre->id]);
                }
            }
        }

        if ($removedPhases) {
            PhaseUser::whereIn('phase_id', $removedPhases)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
        }
    }

    public function setAddedPhaseUsers($addedPhases)
    {
    }

    public function setRemovedPhaseUsers($removedPhases)
    {
        if ($removedPhases) {
            PhaseUser::whereIn('phase_id', $removedPhases)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
        }
    }

    public function removePhaseUsers($project)
    {
        $phases = $project->phases;
        foreach ($phases as $phase) {
            $centres = $phase->centres;
            if ($centres) {
                foreach ($centres as $centre) {
                    if ($centre->auto_update_phase == Centre::ACTIVE_STATUS) {
                        $users = $centre->users->pluck('id')->toArray();
                        // $phase->users()->detach($users);
                        PhaseUser::where('phase_id', $phase->id)->whereNull('deleted_at')->whereIn('user_id', $users)->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
                        $phase->centres()->detach($centre);
                    }
                }
            }
        }
    }
}
