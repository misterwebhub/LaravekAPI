<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\v1\MqopsTotRequest;
use App\Http\Resources\v1\MqopsTotResource;
use App\Models\MqopsTot;
use App\Models\CentreProject;
use App\Models\MqopsTotType;
use App\Repositories\v1\MqopsTotRepository;

class MqopsTotController extends Controller
{
    private $mqopsTotRepository;
    /**
     * @param MqopsTotRepository $mqopsTotRepository
     */
    public function __construct(MqopsTotRepository $mqopsTotRepository)
    {
        $this->mqopsTotRepository = $mqopsTotRepository;
        $this->middleware(
            'permission:mqops.access',
            ['only' => ['index', 'store', 'show', 'update', 'destroy']]
        );
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $tot = $this->mqopsTotRepository->index($request->all());
        return MqopsTotResource::collection($tot);
    }

    /**
     * @param MqopsTotRequest $request
     *
     * @return [type]
     */
    public function store(MqopsTotRequest $request)
    {
        $user = $request->user();
        $tot = $this->mqopsTotRepository->store($request->all(), $user);
        return (new MqopsTotResource($tot))
            ->additional(['message' => trans('admin.mqops_tot_added')]);
    }

    /**
     * @param MqopsTotRequest $request
     * @param mixed $tot
     *
     * @return [type]
     */
    public function update(MqopsTotRequest $request, MqopsTot $tot)
    {
        $user = $request->user();
        $tot = $this->mqopsTotRepository->update($request->all(), $tot, $user);
        return (new MqopsTotResource($tot))
            ->additional(['message' => trans('admin.mqops_tot_updated')]);
    }

    /**
     * @param mixed $tot
     *
     * @return [type]
     */
    public function show(MqopsTot $tot)
    {
        return new MqopsTotResource($tot);
    }

    /**
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy(MqopsTot $tot)
    {
        $this->mqopsTotRepository->destroy($tot);
        return response(['message' => trans('admin.mqops_tot_deleted')]);
    }


    /**
     *
     * @return [json]
     */
    public function getTotType()
    {
        return response(['data' => MqopsTotType::all()]);
    }

    /**
     *
     * @return [json]
     */
    public function getProjectCentre(Request $request)
    {
        $states = $this->mqopsTotRepository->getProjectCentre($request->all());
        return response(['data' => $states]);
    }

    /**
     * get projects
     * @return [json]
     */
    public function getProjects()
    {
        $project = CentreProject::leftJoin('centres', 'centres.id', 'centre_project.centre_id')
            ->leftJoin('projects', 'projects.id', 'centre_project.project_id')
            ->whereNotNull('centres.state_id')
            ->selectRaw('project_id,projects.name')->distinct()->get();
        foreach ($project as $key => $proj) {
            $projects[$key]['id'] = $proj['project_id'];
            $projects[$key]['name'] = $proj['name'];
        }
        return response(['data' => $projects]);
    }

    public function exportSession(Request $request)
    {
        $filePath = $this->mqopsTotRepository->exportTot($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);
    }
}
