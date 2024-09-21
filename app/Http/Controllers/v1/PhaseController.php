<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\PhaseRequest;
use App\Http\Requests\v1\PhaseListRequest;
use App\Http\Resources\v1\PhaseResource;
use App\Http\Resources\v1\CentreResource;
use App\Models\Batch;
use App\Models\Phase;
use App\Models\Centre;
use App\Models\User;
use App\Repositories\v1\PhaseRepository;
use Illuminate\Http\Request;

class PhaseController extends Controller
{
    private $phaseRepository;

    /**
     * @param PhaseRepository $phaseRepository
     */
    public function __construct(PhaseRepository $phaseRepository)
    {
        $this->phaseRepository = $phaseRepository;
        $this->middleware('permission:phase.view', ['only' => ['index', 'listPhase']]);
        $this->middleware('permission:phase.create', ['only' => ['store']]);
        $this->middleware('permission:phase.update', ['only' => ['show', 'update']]);
        $this->middleware('permission:phase.destroy', ['only' => ['destroy']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $projects = $this->phaseRepository->index($request->all());
        return PhaseResource::collection($projects);
    }

    /**
     * @param PhaseRequest $request
     *
     * @return [json]
     */
    public function store(PhaseRequest $request)
    {
        $phase = $this->phaseRepository->store($request->all());
        return (new PhaseResource($phase))
            ->additional(['message' => trans('admin.phase_added')]);
    }

    /**
     * @param Phase $phase
     *
     * @return [json]
     */
    public function show(Phase $phase)
    {
        return new PhaseResource($phase);
    }


    /**
     * @param PhaseRequest $request
     * @param Phase $phase
     *
     * @return [json]
     */
    public function update(PhaseRequest $request, Phase $phase)
    {
        $phases = $this->phaseRepository->update($request->all(), $phase);
        return (new PhaseResource($phases))
            ->additional(['message' => trans('admin.phase_updated')]);
    }


    /**
     * @param Phase $phase
     *
     * @return [json]
     */
    public function destroy(Phase $phase)
    {
        $this->phaseRepository->destroy($phase);
        return response(['message' => trans('admin.phase_deleted')], 200);
    }

    /**
     * @param $phase
     *
     * @return [json]
     */
    public function getPhaseList(PhaseListRequest $request)
    {
        $phases = $this->phaseRepository->getPhaseList($request->all());
        return PhaseResource::collection($phases);
    }

    /**
     * @param $phase
     *
     * @return [json]
     */
    public function getPhaseListCentre(PhaseListRequest $request)
    {
        $phases = $this->phaseRepository->getPhaseListCentre($request->all());
        return PhaseResource::collection($phases);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function listPhase(Request $request, Batch $batch)
    {
        $phases = $this->phaseRepository->listPhase($request->all(), $batch, $request->user());
        return PhaseResource::collection($phases['phases'])->additional(['total_without_filter' => $phases['total_count']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function assignPhase(Request $request, Batch $batch)
    {
        $batch = $this->phaseRepository->assignPhase($request->all(), $batch);
        return PhaseResource::collection($batch->phases)
            ->additional(['message' => trans('admin.phase_added')]);
    }


    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function assignPhaseToCentre(Request $request, centre $centre)
    {
        $centre = $this->phaseRepository->assignPhaseToCentre($request->all(), $centre);
        return PhaseResource::collection($centre->phases()->get())
            ->additional(['message' => trans('admin.phase_added')]);
    }



    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function listCentrePhase(Request $request, centre $centre)
    {
        $phases = $this->phaseRepository->listCentrePhase($request->all(), $centre);
        return PhaseResource::collection($phases['phases'])->additional(['total_without_filter' => $phases['total_count']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function listCentreProjectPhase(Request $request, centre $centre)
    {
        $phases = $this->phaseRepository->listCentreProjectPhase($request->all(), $centre);
        return PhaseResource::collection($phases['phases'])->additional(['total_without_filter' => $phases['total_count']]);
    }

    public function assignSubjectToPhase(Request $request, Phase $phase)
    {
        $phase = $this->phaseRepository->assignSubjectToPhase($request->all(), $phase);
        return PhaseResource::collection($phase->subjects()->where('deleted_at', null)->get())
            ->additional(['message' => trans('admin.phase_subject_updated')]);
    }

    public function listSubjectInPhase(Phase $phase)
    {
        return PhaseResource::collection($phase->subjects()->where('deleted_at', null)->get());
    }


    public function phaseToUserMapping(Request $request)
    {
        $this->phaseRepository->phaseToUserMapping($request->all());
        return response(['message' => trans('admin.phase_added')]);
    }

    public function removePhase(Request $request, Centre $centre)
    {
        $this->phaseRepository->removePhase($request->all(), $centre);
        return response(['message' => trans('admin.centre_phase_deleted')]);
    }


    /**
     * @param Request $request
     *
     * @return [json]
     */

    public function listPhaseForUser(User $user)
    {
        $data = PhaseResource::collection($user->phases()->distinct('name')->whereNull('phase_users.deleted_at')->get());
        return $data;
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function assignPhaseToUser(Request $request, User $user)
    {
        $user = $this->phaseRepository->assignPhaseToUser($request->all(), $user);
        return PhaseResource::collection($user->phases()->get())
            ->additional(['message' => trans('admin.phase_added')]);
    }
}
