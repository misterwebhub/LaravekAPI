<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CentreHeadRequest;
use App\Http\Requests\v1\UpdateStatusCentreHeadRequest;
use App\Http\Resources\v1\CentreHeadResource;
use App\Models\User;
use App\Models\Centre;
use App\Repositories\v1\CentreHeadRepository;
use Illuminate\Http\Request;

class CentreHeadController extends Controller
{
    private $centreHeadRepository;
    /**
     * @param CentreHeadRepository $centreHeadRepository
     */
    public function __construct(CentreHeadRepository $centreHeadRepository)
    {
        $this->centreHeadRepository = $centreHeadRepository;
        $this->middleware('permission:centre.view', ['only' => ['index']]);
        $this->middleware('permission:centre.create', ['only' => ['store']]);
        $this->middleware('permission:centre.update', ['only' => ['edit', 'update', 'updateStatus']]);
        $this->middleware('permission:centre.destroy', ['only' => ['destroy']]);
    }


    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function index(Request $request, Centre $centre)
    {
        $this->authorize('view', $centre);
        $centreHeads = $this->centreHeadRepository->index($request->all(), $centre);
        return CentreHeadResource::collection($centreHeads['centreHead'])
            ->additional(['total_without_filter' => $centreHeads['total_count']]);
    }


    /**
     * @param CentreHeadRequest $request
     *
     * @return [type]
     */
    public function store(CentreHeadRequest $request, Centre $centre)
    {
        $this->authorize('create', $centre);
        $centreHead = $this->centreHeadRepository->store($request->all(), $centre);
        return (new CentreHeadResource($centreHead))
            ->additional(['message' => trans('admin.centre_head_added')]);
    }


    /**
     * @param User $centreHead
     *
     * @return [type]
     */
    public function edit(User $centreHead)
    {
        $this->authorize('view', Centre::find($centreHead->centre_id));
        return new CentreHeadResource($centreHead);
    }


    /**
     * @param CentreHeadRequest $request
     * @param User $centreHead
     *
     * @return [type]
     */
    public function update(CentreHeadRequest $request, User $centreHead)
    {
        $this->authorize('update', Centre::find($centreHead->centre_id));
        $centreHead = $this->centreHeadRepository->update($request->all(), $centreHead);
        return (new CentreHeadResource($centreHead))
            ->additional(['message' => trans('admin.centre_head_updated')]);
    }


    /**
     * @param User $centrehead
     *
     * @return [type]
     */
    public function destroy(User $centreHead)
    {
        $this->authorize('delete', Centre::find($centreHead->centre_id));
        $this->centreHeadRepository->destroy($centreHead);
        return response(['message' => trans('admin.centre_head_deleted')], 200);
    }


    /**
     * @param UpdateStatusCentreHeadRequest $request
     * @param User $centreHead
     *
     * @return [type]
     */
    public function updateStatus(UpdateStatusCentreHeadRequest $request, User $centreHead)
    {
        $this->authorize('update', Centre::find($centreHead->centre_id));
        $centreHead = $this->centreHeadRepository->updateStatus($request->all(), $centreHead);
        return (new CentreHeadResource($centreHead))
            ->additional(['message' => trans('admin.centre_head_status_updated')]);
    }
}
