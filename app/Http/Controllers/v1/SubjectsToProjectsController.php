<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\PhaseRequest;
use App\Http\Resources\v1\SubjectsToProjectsResource;
use App\Models\Project;
use App\Repositories\v1\SubjectsToProjectsRepository;
use Illuminate\Http\Request;

class SubjectsToProjectsController extends Controller
{
    private $subjectsToProjectsRepository;

    /**
     * @param SubjectsToProjectsRepository $subjectsToProjectRepository
     */
    public function __construct(SubjectsToProjectsRepository $subjectsToProjectsRepository)
    {
        $this->subjectsToProjectsRepository = $subjectsToProjectsRepository;
        $this->middleware('permission:project.update', ['only' => ['listSubject', 'assignSubject']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function listSubject(Project $project)
    {
        $this->authorize('view', $project);
        return SubjectsToProjectsResource::collection($project->subjects);
    }

    /**
     * @param PhaseRequest $request
     *
     * @return [json]
     */
    public function assignSubject(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        $project = $this->subjectsToProjectsRepository->assignSubject($request->all(), $project);
        return SubjectsToProjectsResource::collection($project->subjects)
            ->additional(['message' => trans('admin.project_subject_updated')]);
    }
}
