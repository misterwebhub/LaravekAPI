<?php

namespace App\Repositories\v1;

use App\Models\Placement;
use App\Models\PlacementStatus;
use App\Models\PlacementType;
use Spatie\QueryBuilder\QueryBuilder;
use App\Services\PlacementSectorCustomSort;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\Filter\PlacementCustomFilter;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Class PlacementRepository
 * @package App\Repositories
 */
class PlacementRepository
{
    /**
     * List all placements
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request, $userId)
    {
        $placements = QueryBuilder::for(Placement::class)
            ->allowedFilters([
                'company', 'designation', 'sector.name', 'salary',
                AllowedFilter::custom('search_value', new PlacementCustomFilter()),
            ])
            ->allowedSorts(
                [
                    'company', 'designation', 'salary',
                    AllowedSort::custom('sector.name', new PlacementSectorCustomSort()),
                ]
            )
            ->where('tenant_id', getTenant())
            ->where('user_id', $userId)
            ->latest()
            ->paginate($request['limit'] ?? null);
        return $placements;
    }

    /**
     * Create a new placement
     *
     * @param mixed $request
     * @param mixed $userId
     *
     * @return [type]
     */
    public function store($request, $userId)
    {
        $placement = new Placement();
        $placementType = $request['placement_type'];
        $placementDetail = Placement::where('user_id', $userId)
            ->where("placement_type_id", $placementType)
            ->first();

        if (!$placementDetail) {
            $placement = $this->setPlacement($request, $placement);
            $placement->user_id = $userId;
            $placement->save();
            $placement->message = "Added";
        } else {
            $placement = $this->update($request, $placementDetail);
            $placement->message = "Updated";
        }
        return $placement;
    }

    /**
     * Update Placement
     * @param mixed $request
     * @param mixed $placement
     *
     * @return [json]
     */
    public function update($request, $placement)
    {
        $placement = $this->setPlacement($request, $placement);
        $placement->update();
        return $placement;
    }

    /**
     * Get placement status
     *
     * @param mixed $placementType
     *
     * @return [type]
     */
    public function getPlacementStatus($placementType)
    {
        $placementTypeDetail = PlacementType::where("id", $placementType)->first();
        if ($placementTypeDetail->type == PlacementType::TYPE1) {
            $placementStatusDetail = PlacementStatus::whereNull("placement_type")
                ->orWhere("placement_type", PlacementType::TYPE2)
                ->get();
        } else {
            $placementStatusDetail = PlacementStatus::whereNull("placement_type")
                ->orWhere("placement_type", PlacementType::TYPE3)
                ->get();
        }
        return $placementStatusDetail;
    }

    /**
     * Set placement Data
     * @param mixed $request
     * @param mixed $placement
     *
     * @return [collection]
     */
    private function setPlacement($request, $placement)
    {
        $placement->tenant_id = getTenant();
        $placement->centre_id = isset($request['centre']) ? (($request['centre']) ?: null) : null;
        $placement->placement_type_id = $request['placement_type'];
        $placement->placement_status_id = $request['placement_status'];
        $placement->offerletter_type_id =
            isset($request['offerletter_type']) ? (($request['offerletter_type']) ?: null) : null;
        $placement->offerletter_status_id =
            isset($request['offerletter_status']) ? (($request['offerletter_status']) ?: null) : null;
        $placement->placement_course_id = isset($request['course']) ? (($request['course']) ?: null) : null;
        $placement->company = isset($request['company']) ? (($request['company']) ?: null) : null;
        $placement->designation = isset($request['designation']) ? (($request['designation']) ?: null) : null;
        $placement->sector_id = isset($request['sector']) ? (($request['sector']) ?: null) : null;
        $placement->location_id = isset($request['district']) ? (($request['district']) ?: null) : null;
        $placement->salary = isset($request['salary']) ? (($request['salary']) ?: null) : null;
        $placement->reason = isset($request['reason']) ? (($request['reason']) ?: null) : null;
        return $placement;
    }
}
