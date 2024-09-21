<?php

namespace App\Repositories\v1;

use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Program;

/**
 * [Description ProgramRepository]
 */
class ProgramRepository
{
    /**
     * List all programs
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request, $user)
    {
        $programs = QueryBuilder::for(Program::class)
            ->where('tenant_id', getTenant());
        $totalCount = $programs->get()->count();
        $programs = $programs
            ->allowedFilters(['name', 'status'])
            ->allowedSorts(['name', 'status'])
            ->when($user->hasPermissionTo('program.administrator'), function ($query) use ($user) {
                return $query->where('id', $user->program_id);
            })
            ->when($user->hasPermissionTo('organisation.administrator'), function ($query) use ($user) {
                $programIds = $user->organisation->program->pluck('id')->toArray();
                return $query->whereIn('id', $programIds);
            })
            ->when($user->hasPermissionTo('centre.administrator'), function ($query) use ($user) {
                $programIds = $user->centre->organisation->program->pluck('id')->toArray();
                return $query->whereIn('id', $programIds);
            })
            ->when(
                $user->hasPermissionTo('project.administrator'),
                function ($query) use ($user) {
                    $programIds = $user->project->program->pluck('id')->toArray();
                    return $query->whereIn('id', $programIds);
                }
            )
            ->latest()
            ->paginate($request['limit'] ?? null);
        return ['programs' => $programs, 'total_count' => $totalCount];
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
        $program = new Program();
        $program = $this->setProgram($request, $program);
        $program->save();
        return $program;
    }

    /**
     * Delete a Centre
     * @param mixed $program
     *
     * @return [type]
     */
    public function destroy($program)
    {
        $program->projects()->delete();
        $program->delete();
    }

    /**
     * Update Program
     * @param mixed $request
     * @param mixed $program
     *
     * @return [json]
     */
    public function update($request, $program)
    {
        $program = $this->setProgram($request, $program);
        $program->update();
        return $program;
    }

    /**
     * Update status of program
     * @param mixed $request
     * @param mixed $program
     *
     * @return [type]
     */
    public function updateStatus($request, $program)
    {
        $program->status = $request['status'];
        $program->update();
        return $program;
    }

    /**
     * Set Program Data
     * @param mixed $request
     * @param mixed $program
     *
     * @return [collection]
     */
    private function setProgram($request, $program)
    {
        $program->name = $request['name'];
        $program->tenant_id = getTenant();
        return $program;
    }
}
