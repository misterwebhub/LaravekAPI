<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\OrganisationHeadRequest;
use App\Http\Requests\v1\UpdateStatusOrganisationHeadRequest;
use App\Http\Resources\v1\OrganisationHeadResource;
use App\Models\User;
use App\Models\Organisation;
use App\Repositories\v1\OrganisationHeadRepository;
use Illuminate\Http\Request;

class OrganisationHeadController extends Controller
{
    private $organisationHeadRepository;
    /**
     * @param OrganisationHeadRepository $organisationHeadRepository
     */
    public function __construct(OrganisationHeadRepository $organisationHeadRepository)
    {
        $this->organisationHeadRepository = $organisationHeadRepository;
        $this->middleware('permission:organisation.view', ['only' => ['index']]);
        $this->middleware('permission:organisation.create', ['only' => ['store']]);
        $this->middleware(
            'permission:organisation.update',
            ['only' => ['edit', 'update', 'updateStatus']]
        );
        $this->middleware('permission:organisation.destroy', ['only' => ['destroy']]);
    }


    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function index(Request $request, Organisation $organisation)
    {
        $this->authorize('view', $organisation);
        $organisationHeads = $this->organisationHeadRepository->index($request->all(), $organisation);
        return OrganisationHeadResource::collection($organisationHeads);
    }


    /**
     * @param OrganisationHeadRequest $request
     *
     * @return [type]
     */
    public function store(OrganisationHeadRequest $request, Organisation $organisation)
    {
        $this->authorize('update', $organisation);
        $organisationHead = $this->organisationHeadRepository->store($request->all(), $organisation);
        return (new OrganisationHeadResource($organisationHead))
            ->additional(['message' => trans('admin.organisation_head_added')]);
    }


    /**
     * @param User $organisationHead
     *
     * @return [type]
     */
    public function edit(User $organisationHead)
    {
        $this->authorize('update', Organisation::find($organisationHead->organisation_id));
        return new OrganisationHeadResource($organisationHead);
    }


    /**
     * @param OrganisationHeadRequest $request
     * @param User $organisationHead
     *
     * @return [type]
     */
    public function update(OrganisationHeadRequest $request, User $organisationHead)
    {
        $this->authorize('update', Organisation::find($organisationHead->organisation_id));
        $organisationHead = $this->organisationHeadRepository->update($request->all(), $organisationHead);
        return (new OrganisationHeadResource($organisationHead))
            ->additional(['message' => trans('admin.organisation_head_updated')]);
    }


    /**
     * @param User $organisationhead
     *
     * @return [type]
     */
    public function destroy(User $organisationHead)
    {
        $this->authorize('delete', Organisation::find($organisationHead->organisation_id));
        $this->organisationHeadRepository->destroy($organisationHead);
        return response(['message' => trans('admin.organisation_head_deleted')], 200);
    }


    /**
     * @param UpdateStatusOrganisationHeadRequest $request
     * @param User $organisationHead
     *
     * @return [type]
     */
    public function updateStatus(UpdateStatusOrganisationHeadRequest $request, User $organisationHead)
    {
        $this->authorize('update', Organisation::find($organisationHead->organisation_id));
        $organisationHead = $this->organisationHeadRepository->updateStatus($request->all(), $organisationHead);
        return (new OrganisationHeadResource($organisationHead))
            ->additional(['message' => trans('admin.organisation_head_status_updated')]);
    }
}
