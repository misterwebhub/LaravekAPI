<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\PhaseRequest;
use App\Http\Resources\v1\ProjectsToCentreResource;
use App\Http\Resources\v1\CentreToProjectResource;
use App\Http\Resources\v1\ProjectHeadResource;
use App\Models\Centre;
use App\Models\Project;
use App\Repositories\v1\ProjectsToCentreRepository;
use Illuminate\Http\Request;
use App\Http\Resources\v1\PhaseResource;

class ProjectsToCentreController extends Controller
{
    private $projectsToCentreRepository;

    /**
     * @param ProjectsToCentreRepository $projectsToCentreRepository
     */
    public function __construct(ProjectsToCentreRepository $projectsToCentreRepository)
    {
        $this->projectsToCentreRepository = $projectsToCentreRepository;
        $this->middleware('permission:centre.view', ['only' => ['listProject', 'listCentre']]);
        $this->middleware('permission:centre.update', ['only' => ['assignProject', 'deleteProject']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function listProject(Request $request, Centre $centre)
    {
        $this->authorize('view', $centre);
        $projects = $this->projectsToCentreRepository->listProject($request->all(), $centre, $request->user());
        // $centrephases = $this->projectsToCentreRepository->listProjectCentrePhases($centre->id);
        return ProjectsToCentreResource::collection($projects);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function assignProject(Request $request, Centre $centre)
    {
        $this->authorize('update', $centre);
        $project = $this->projectsToCentreRepository->assignProject($request->all(), $centre);
        return ProjectsToCentreResource::collection($centre->projects)
            ->additional(['message' => trans('admin.centre_project_added')]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function editProject(Request $request, Centre $centre)
    {
        $this->authorize('update', $centre);
        $project = $this->projectsToCentreRepository->editProject($request->all(), $centre);
        return ProjectsToCentreResource::collection($centre->projects)
            ->additional(['message' => trans('admin.centre_project_edited')]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function deleteProject(Request $request, Centre $centre)
    {
        $this->authorize('delete', $centre);
        $centre = $this->projectsToCentreRepository->deleteProject($request->all(), $centre);

        return ['message' => trans('admin.centre_project_deleted')];
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function listCentre(Request $request, Project $project)
    {
        $this->authorize('view', $project);
        $centres = $this->projectsToCentreRepository->listCentre($request->all(), $project, $request->user());
        return CentreToProjectResource::collection($centres['centres'])->additional(['total_without_filter' => $centres['total_count']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function listProjectHead(Request $request, Project $project)
    {
        $projectHeads = $this->projectsToCentreRepository->listProjectHead($request->all(), $project);
        return ProjectHeadResource::collection($projectHeads['projectHead'])
            ->additional(['total_without_filter' => $projectHeads['total_count']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function listPhase(Request $request, Project $project)
    {
        $this->authorize('view', $project);
        $phases = $this->projectsToCentreRepository->listPhase($request->all(), $project, $request->user());
        return PhaseResource::collection($phases['phases'])->additional(['total_without_filter' => $phases['total_count']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function listProjectPhase(Request $request, Centre $centre)
    {
        $this->authorize('update', $centre);
        $phases = $this->projectsToCentreRepository->listProjectPhase($request->all(), $centre);
        return PhaseResource::collection($phases['phases']);
    }
}
