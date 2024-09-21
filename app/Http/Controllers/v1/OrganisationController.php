<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\OrganisationRequest;
use App\Http\Requests\v1\UpdateStatusRequest;
use App\Http\Requests\v1\OrganisationImportRequest;
use App\Http\Resources\v1\OrganisationResource;
use App\Models\Organisation;
use App\Repositories\v1\OrganisationRepository;
use Illuminate\Http\Request;

class OrganisationController extends Controller
{
    private $organisationRepository;
    /**
     * @param OrganisationRepository $organisationRepository
     */
    public function __construct(OrganisationRepository $organisationRepository)
    {
        $this->organisationRepository = $organisationRepository;
        $this->middleware('permission:organisation.view', ['only' => ['index', 'show', 'exportOrganisation']]);
        $this->middleware('permission:organisation.create', ['only' => ['store', 'importOrganisation']]);
        $this->middleware('permission:organisation.update', ['only' => ['update', 'updateStatus', 'importOrganisation']]);
        $this->middleware('permission:organisation.destroy', ['only' => ['destroy']]);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function index(Request $request)
    {
        $organisations = $this->organisationRepository->index($request->all(), $request->user());
        return OrganisationResource::collection($organisations['organisations'])
            ->additional(['total_without_filter' => $organisations['total_count']]);
    }


    /**
     * @param OrganisationRequest $request
     *
     * @return [type]
     */
    public function store(OrganisationRequest $request)
    {
        $this->authorize('create', Organisation::class);
        $user = $request->user();
        $organisation = $this->organisationRepository->store($request->all(), $user);
        return (new OrganisationResource($organisation))
            ->additional(['message' => trans('admin.organisation_added')]);
    }

    /**
     * @param Organisation $organisation
     *
     * @return [type]
     */
    public function show(Organisation $organisation)
    {
        $this->authorize('view', $organisation);
        return new OrganisationResource($organisation);
    }

    /**
     * @param OrganisationRequest $request
     * @param Organisation $org
     *
     * @return [type]
     */
    public function update(OrganisationRequest $request, Organisation $organisation)
    {
        $this->authorize('update', $organisation);
        $organisation = $this->organisationRepository->update($request->all(), $organisation);
        return (new OrganisationResource($organisation))
            ->additional(['message' => trans('admin.organisation_updated')]);
    }


    /**
     * @param Organisation $organisation
     *
     * @return [type]
     */
    public function destroy(Organisation $organisation)
    {
        $this->authorize('delete', $organisation);
        $this->organisationRepository->destroy($organisation);
        return response(['message' => trans('admin.organisation_deleted')], 200);
    }


    /**
     * @param UpdateStatusRequest $request
     * @param Organisation $organisation
     *
     * @return [type]
     */
    public function updateStatus(UpdateStatusRequest $request, Organisation $organisation)
    {
        $this->authorize('update', $organisation);
        $organisation = $this->organisationRepository->updateStatus($request->all(), $organisation);
        return (new OrganisationResource($organisation))
            ->additional(['message' => trans('admin.organisation_updated')]);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function exportOrganisation(Request $request)
    {
        $filePath = $this->organisationRepository->exportOrganisation($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function importOrganisation(OrganisationImportRequest $request)
    {
        $importData = $this->organisationRepository->importOrganisation($request->all());
        return response([
            'data' => $importData,
        ], 200);
    }
}
