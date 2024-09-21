<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CentreRequest;
use App\Http\Requests\v1\UpdateStatusCentreRequest;
use App\Http\Requests\v1\UpdateConfigureBatchAlumniRequest;
use App\Http\Requests\v1\CentreImportRequest;
use App\Http\Resources\v1\CentreResource;
use App\Http\Resources\v1\TradeResource;
use App\Models\Centre;
use App\Models\Organisation;
use App\Repositories\v1\CentreRepository;
use Illuminate\Http\Request;
use App\Http\Requests\v1\UpdateConfigureAllowBatchRequest;

class CentreController extends Controller
{
    private $centreRepository;

    /**
     * @param CentreRepository $centreRepository
     */
    public function __construct(CentreRepository $centreRepository)
    {
        $this->centreRepository = $centreRepository;
        $this->middleware('permission:centre.view', ['only' => ['index', 'show', 'exportCentre']]);
        $this->middleware('permission:centre.create', ['only' => ['store', 'importCentre']]);
        $this->middleware('permission:centre.update', ['only' => ['update', 'updateStatus', 'importCentre']]);
        $this->middleware('permission:centre.destroy', ['only' => ['destroy']]);
        $this->middleware('permission:centre.update', ['only' => ['configureBatchAlumni']]);
        $this->middleware('permission:centre.update', ['only' => ['configureAllowBatch']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {

        $centres = $this->centreRepository->index($request->all(), $request->user());
        return CentreResource::collection($centres['centres'])->additional([
            'total_without_filter' => $centres['total_count']
        ]);
    }

    /**
     * @param CentreRequest $request
     *
     * @return [type]
     */
    public function store(CentreRequest $request)
    {
        $this->authorize('update', Organisation::find($request->organisation));
        $user = $request->user();
        $centre = $this->centreRepository->store($request->all(), $user);
        return (new CentreResource($centre))
            ->additional(['message' => trans('admin.centre_added')]);
    }

    /**
     * @param mixed $centre
     *
     * @return [type]
     */
    public function show(Centre $centre)
    {
        $this->authorize('view', $centre);
        return new CentreResource($centre);
    }

    /**
     * @param CentreRequest $request
     * @param mixed $centre
     *
     * @return [type]
     */
    public function update(CentreRequest $request, Centre $centre)
    {
        $this->authorize('update', $centre);
        $centre = $this->centreRepository->update($request->all(), $centre);
        return (new CentreResource($centre))
            ->additional(['message' => trans('admin.centre_updated')]);
    }

    /**
     * @param mixed $centre
     *
     * @return [type]
     */
    public function destroy(Centre $centre)
    {
        $this->authorize('delete', $centre);
        $this->centreRepository->destroy($centre);
        return response(['message' => trans('admin.centre_deleted')], 200);
    }

    /**
     * @param Request $request
     * @param mixed $centre
     *
     * @return [type]
     */
    public function updateStatus(UpdateStatusCentreRequest $request, Centre $centre)
    {
        $this->authorize('update', $centre);
        $centre = $this->centreRepository->updateStatus($request->all(), $centre);
        return (new CentreResource($centre))
            ->additional(['message' => trans('admin.centre_status_change')]);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function exportCentre(Request $request)
    {
        $filePath = $this->centreRepository->exportCentre($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function importCentre(CentreImportRequest $request)
    {
        $importData = $this->centreRepository->importCentre($request->all());
        return response([
            'data' => $importData,
        ], 200);
    }


    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function getTrades(Centre $centre)
    {
        $this->authorize('view', $centre);
        $trades = $this->centreRepository->getTrades($centre);
        return TradeResource::collection($trades);
    }

    /**
     * @param UpdateConfigureBatchAlumniRequest $request
     *
     * @return [type]
     */
    public function configureBatchAlumni(UpdateConfigureBatchAlumniRequest $request)
    {
        $centre = $this->centreRepository->configureBatchAlumni($request->all());
        return (new CentreResource($centre))
            ->additional(['message' => trans('admin.configured_batch_alumni')]);
    }

    /**
     * @param UpdateConfigureAllowBatchRequest $request
     *
     * @return [type]
     */
    public function configureAllowBatch(UpdateConfigureAllowBatchRequest $request)
    {
        $centre = $this->centreRepository->configureAllowBatch($request->all());
        return (new CentreResource($centre))
            ->additional(['message' => trans('admin.configured_allow_batch')]);
    }
     /**
     * @param Request $request
     *
     * @return [type]
     */
    public function importCentreDistrictChange(CentreImportRequest $request)
    {
        $importData = $this->centreRepository->importCentreDistrictChange($request->all());
        return response([
            'data' => $importData,
        ], 200);
    }
      /**
     * @param Request $request
     *
     * @return [type]
     */
    public function getMismatchStatesOfCentre(CentreImportRequest $request)
    {
        $importData = $this->centreRepository->getMismatchStatesOfCentre($request->all());
        return response([
            'data' => $importData,
        ], 200);
    }
}
