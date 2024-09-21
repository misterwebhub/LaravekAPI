<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\PlacementRequest;
use App\Http\Resources\v1\PlacementResource;
use App\Http\Resources\v1\PlacementStatusResource;
use App\Models\User;
use App\Models\Placement;
use App\Repositories\v1\PlacementRepository;
use Illuminate\Http\Request;

class PlacementController extends Controller
{
    private $placementRepository;
    /**
     * @param placementRepository $placementRepository
     */
    public function __construct(PlacementRepository $placementRepository)
    {
        $this->placementRepository = $placementRepository;
        $this->middleware(
            'permission:learner.view',
            ['only' => ['index', 'getPlacementStatus']]
        );
        $this->middleware(
            'permission:learner.update',
            ['only' => ['store', 'edit', 'update']]
        );
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request, $userId)
    {
        $placements = $this->placementRepository->index($request->all(), $userId);
        return PlacementResource::collection($placements)
            ->additional([
                'total_without_filter' => Placement::count(),
            ]);
    }

    /**
     * @param PlacementRequest $request
     *
     * @return [type]
     */
    public function store(PlacementRequest $request, $userId)
    {

        $placement = $this->placementRepository->store($request->all(), $userId);
        if ($placement->message == "Updated") {
            return (new PlacementResource($placement))
                ->additional(['message' => trans('admin.placement_updated')]);
        } elseif ($placement->message == "Added") {
            return (new PlacementResource($placement))
                ->additional(['message' => trans('admin.placement_added')]);
        }
    }

    /**
     * @param placement $placement
     *
     * @return [json]
     */
    public function edit(Placement $placement)
    {
        return new PlacementResource($placement);
    }

    /**
     * @param placementRequest $request
     * @param mixed $id
     *
     * @return [type]
     */
    public function update(PlacementRequest $request, Placement $placement)
    {
        $placement = $this->placementRepository->update($request->all(), $placement);
        return (new PlacementResource($placement))
            ->additional(['message' => trans('admin.placement_updated')]);
    }


    /**
     * @param $placementType
     *
     * @return [json]
     */
    public function getPlacementStatus(Request $request, $placementType)
    {
        $placementStatusDetail = $this->placementRepository->getPlacementStatus($placementType);
        $placementStatus = PlacementStatusResource::collection($placementStatusDetail);
        $device = strtolower($request->header('device'));
        if ($device == User::DEVICE_TYPE2) {
            return response(
                [
                'status' => User::API_SUCCESS_STATUS,
                'message' => trans('placement.success'),
                'data' => $placementStatus
                ]
            );
        } else {
            return $placementStatus;
        }
    }
}
