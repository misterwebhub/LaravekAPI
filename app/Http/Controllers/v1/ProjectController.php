<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\ProjectRequest;
use App\Http\Requests\v1\UpdateStatusRequest;
use App\Http\Resources\v1\ProjectResource;
use App\Models\Program;
use App\Models\Project;
use App\Repositories\v1\ProjectRepository;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    private $projectRepository;

    /**
     * @param ProjectRepository $projectRepository
     */
    public function __construct(ProjectRepository $projectRepository)
    {
        $this->projectRepository = $projectRepository;
        $this->middleware('permission:project.view', ['only' => ['show', 'index']]);
        $this->middleware('permission:project.create', ['only' => ['store']]);
        $this->middleware('permission:project.update', ['only' => ['update', 'updateStatus']]);
        $this->middleware('permission:project.destroy', ['only' => ['destroy']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $projects = $this->projectRepository->index($request->all(), $request->user());
        return ProjectResource::collection($projects['projects'])
            ->additional(['total_without_filter' => $projects['total_count']]);
    }

    /**
     * @param ProjectRequest $request
     *
     * @return [json]
     */
    public function store(ProjectRequest $request)
    {
        $this->authorize('update', Program::find($request->program));
        $project = $this->projectRepository->store($request->all());
        return (new ProjectResource($project))
            ->additional(['message' => trans('admin.project_added')]);
    }

    /**
     * @param Project $project
     *
     * @return [json]
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);
        return new ProjectResource($project);
    }


    /**
     * @param ProjectRequest $request
     * @param Project $project
     *
     * @return [json]
     */
    public function update(ProjectRequest $request, Project $project)
    {
        $this->authorize('update', $project);
        $projects = $this->projectRepository->update($request->all(), $project);
        return (new ProjectResource($projects))
            ->additional(['message' => trans('admin.project_updated')]);
    }


    /**
     * @param Project $project
     *
     * @return [json]
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);
        $this->projectRepository->destroy($project);
        return response(['message' => trans('admin.project_deleted')], 200);
    }


    /**
     * @param UpdateStatusRequest $request
     * @param Project $project
     *
     * @return [json]
     */
    public function updateStatus(UpdateStatusRequest $request, Project $project)
    {
        $this->authorize('update', $project);
        $projects = $this->projectRepository->updateStatus($request->all(), $project);
        return (new ProjectResource($projects))
            ->additional(['message' => trans('admin.project_status_change')]);
    }

    /**
     * List phases for mapping
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function unAssignedPhase(Project $project)
    {
        if ($project->id) {
            $this->authorize('view', $project);
        }
        $phases = $this->projectRepository->unAssignedPhase($project);
        return response([
            'data' => $phases,
        ], 200);
    }
}
