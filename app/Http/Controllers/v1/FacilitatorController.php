<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\FacilitatorRequest;
use App\Http\Requests\v1\UpdateStatusUserRequest;
use App\Http\Requests\v1\FacilitatorImportRequest;
use App\Http\Requests\v1\FacilitatorAssignRequest;
use App\Http\Resources\v1\FacilitatorResource;
use App\Http\Resources\v1\CentreResource;
use App\Http\Resources\v1\UserResource;
use App\Models\MasterTrainerUser;
use App\Models\Centre;
use App\Models\User;
use App\Repositories\v1\FacilitatorRepository;
use Illuminate\Http\Request;

class FacilitatorController extends Controller
{
    private $facilitatorRepository;
    /**
     * @param FacilitatorRepository $facilitatorRepository
     */
    public function __construct(FacilitatorRepository $facilitatorRepository)
    {
        $this->facilitatorRepository = $facilitatorRepository;
        $this->middleware('permission:facilitator.view', ['only' => ['index', 'exportFacilitator']]);
        $this->middleware('permission:facilitator.create', ['only' => ['create', 'store', 'importFacilitator']]);
        $this->middleware(
            'permission:facilitator.update',
            ['only' => [
                'edit', 'update', 'updateStatus', 'assignCentres', 'listCentres',
                'assignFacilitators', 'listFacilitators', 'listFacilitatorsForMasterTrainers', 'importFacilitator'
            ]]
        );
        $this->middleware('permission:facilitator.destroy', ['only' => ['destroy']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $facilitators = $this->facilitatorRepository->index($request->all(), $request->user());
        return FacilitatorResource::collection($facilitators['facilitators'])
            ->additional(['total_without_filter' => $facilitators['total_count']]);
    }

    /**
     * @param FacilitatorRequest $request
     *
     * @return [type]
     */
    public function store(FacilitatorRequest $request)
    {
        $this->authorize('create', Centre::find($request->centre_id));
        $user = $request->user();
        $facilitator = $this->facilitatorRepository->store($request->all(), $user);
        return (new FacilitatorResource($facilitator))
            ->additional(['message' => trans('admin.facilitator_added')]);
    }

    /**
     * @param FacilitatorRequest $request
     * @param mixed $id
     *
     * @return [type]
     */
    public function update(FacilitatorRequest $request, User $facilitator)
    {
        $this->authorize('update', $facilitator);
        $facilitators = $this->facilitatorRepository->update($request->all(), $facilitator);
        return (new FacilitatorResource($facilitators))
            ->additional(['message' => trans('admin.facilitator_updated')]);
    }

    /**
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy(User $facilitator)
    {
        $this->authorize('delete', $facilitator);
        $this->facilitatorRepository->destroy($facilitator);
        return response(['message' => trans('admin.facilitator_deleted')]);
    }

    /**
     * @param user $user
     *
     * @return [json]
     */
    public function edit(User $facilitator)
    {
        $this->authorize('view', $facilitator);
        return new FacilitatorResource($facilitator);
    }

    /**
     * @param UpdateStatusUserRequest $request
     * @param User $user
     *
     * @return [type]
     */
    public function updateStatus(UpdateStatusUserRequest $request, User $facilitator)
    {
        $this->authorize('update', $facilitator);
        $facilitator = $this->facilitatorRepository->updateStatus($request->all(), $facilitator);
        return (new FacilitatorResource($facilitator))
            ->additional(['message' => trans('admin.facilitator_status_updated')]);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function exportFacilitator(Request $request)
    {
        $filePath = $this->facilitatorRepository->exportFacilitator($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function importFacilitator(FacilitatorImportRequest $request)
    {
        $userId = $request->user()->id;
        $importData = $this->facilitatorRepository->importFacilitator($request->all(), $userId);
        return response([
            'data' => $importData,
        ], 200);
    }

    /**
     * @param Request $request
     * @param User $facilitator
     *
     * @return [type]
     */
    public function assignCentres(User $facilitator, Request $request)
    {
        $this->authorize('update', $facilitator);
        $this->facilitatorRepository->assignCentres($facilitator, $request['centres'], 2);
        return CentreResource::collection($facilitator->centres)
            ->additional(['message' => trans('admin.centres_assigned')]);
    }

    /**
     * @param User $facilitator
     *
     * @return [type]
     */
    public function listCentres(User $facilitator)
    {
        $this->authorize('view', $facilitator);
        return CentreResource::collection($facilitator->centres);
    }

    /**
     * @param Request $request
     * @param User $masterTrainer
     *
     * @return [type]
     */
    public function assignFacilitators(User $facilitator, FacilitatorAssignRequest $request)
    {
        $this->facilitatorRepository->assignFacilitators($facilitator, $request['facilitators']);
        return UserResource::collection($facilitator->masterTrainerUsers)
            ->additional(['message' => trans('admin.facilitators_assigned')]);
    }

    /**
     * @param User $facilitator
     *
     * @return [type]
     */
    public function listFacilitatorsForMasterTrainers(Request $request, User $facilitator)
    {
        $request['master_trainer_id'] = $facilitator->id;
        $facilitators = $this->facilitatorRepository->listFacilitatorsForMasterTrainers($request, $facilitator);
        $trainers = MasterTrainerUser::leftJoin('users', 'user_id', 'id')
            ->whereNull('users.deleted_at')->where('master_trainer_id', $facilitator->id)
            ->distinct()->pluck('user_id')->toArray();
        return UserResource::collection($facilitators)->additional(['selected_facilitators' => $trainers]);
    }

    /**
     * @param User $facilitator
     *
     * @return [type]
     */
    public function enableApproval(User $facilitator)
    {
        $this->authorize('update', $facilitator);
        $facilitator = $this->facilitatorRepository->enableApproval($facilitator);
        return (new FacilitatorResource($facilitator))
            ->additional(['message' => trans('admin.facilitator_approval_updated')]);
    }
}
