<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\MqopsCentreVisitRequest;
use App\Http\Resources\v1\MqopsCentreVisitResource;
use App\Models\MqopsCentreVisit;
use App\Repositories\v1\MqopsCentreVisitRepository;
use Illuminate\Http\Request;

class MqopsCentreVisitController extends Controller
{
    private $mqopsCentreVisitRepository;
    /**
     * @param MqopsCentreVisitRepository $mqopsCentreVisitRepository
     */
    public function __construct(mqopsCentreVisitRepository $mqopsCentreVisitRepository)
    {
        $this->mqopsCentreVisitRepository = $mqopsCentreVisitRepository;
        $this->middleware('permission:mqops.full.access', ['only' => ['index', 'store', 'update', 'destroy', 'edit']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $mqopsCentreVisit = $this->mqopsCentreVisitRepository->index($request->all());
        return MqopsCentreVisitResource::collection($mqopsCentreVisit);
    }

    /**
     * @param MqopsCentreVisitRequest $request
     *
     * @return [type]
     */
    public function store(MqopsCentreVisitRequest $request)
    {
        $user = $request->user();
        $mqopsCentreVisit = $this->mqopsCentreVisitRepository->store($request->all(), $user);
        return (new mqopsCentreVisitResource($mqopsCentreVisit))
            ->additional(['message' => trans('admin.mqops_centre_visit_added')]);
    }

    /**
     * @param MqopsCentreVisitRequest $request
     * @param mixed $mqopsCentreVisit
     *
     * @return [type]
     */
    public function update(MqopsCentreVisitRequest $request, MqopsCentreVisit $mqopsCentreVisit)
    {
        $user = $request->user();
        $mqopsCentreVisit = $this->mqopsCentreVisitRepository->update(
            $request->all(),
            $mqopsCentreVisit,
            $user
        );
        return (new MqopsCentreVisitResource($mqopsCentreVisit))
            ->additional(['message' => trans('admin.mqops_centre_visit_updated')]);
    }

    /**
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy(MqopsCentreVisit $mqopsCentreVisit)
    {
        $this->mqopsCentreVisitRepository->destroy($mqopsCentreVisit);
        return response(['message' => trans('admin.mqops_centre_visit_deleted')]);
    }

    /**
     * @param MqopsCentreVisit $mqopsCentreVisit
     *
     * @return [json]
     */
    public function edit(MqopsCentreVisit $mqopsCentreVisit)
    {
        return new MqopsCentreVisitResource($mqopsCentreVisit);
    }

    public function exportCentre(Request $request)
    {
        $filePath = $this->mqopsCentreVisitRepository->exportCentre($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);

    }
}
