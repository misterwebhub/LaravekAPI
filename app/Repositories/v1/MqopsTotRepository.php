<?php

namespace App\Repositories\v1;

use App\Models\MqopsTot;
use App\Models\MqopsDocument;
use App\Models\MqopsTotSummaryProject;
use App\Models\CentreProject;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\MqopsTotUserCustomSort;
use App\Services\MqopsTotCentreCustomSort;
use App\Services\MqopsTotStateCustomSort;
use App\Services\MqopsTotTypeCustomSort;
use Carbon\Carbon;
use App\Exports\MqopsTotExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class MqopsTotRepository
 * @package App\Repositories
 */
class MqopsTotRepository
{
    /**
     * List all Mqops Activities
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request)
    {
        return QueryBuilder::for(MqopsTot::class)
            ->allowedSorts(
                [
                    'start_date', 'end_date',
                    AllowedSort::custom('user_name', new MqopsTotUserCustomSort()),
                    AllowedSort::custom('centre_type_name', new MqopsTotCentreCustomSort()),
                    AllowedSort::custom('state_name', new MqopsTotStateCustomSort()),
                    AllowedSort::custom('tot_name', new MqopsTotTypeCustomSort()),
                ]
            )
            ->where('tenant_id', getTenant())
            ->when(!auth()->user()->hasRole('super-admin'), function ($q) {
                $q->where('user_id', auth()->user()->id);
            })
            ->latest()
            ->paginate($request['limit'] ?? null);
    }

    /**
     * Create a new Mqops Tot
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request, $user)
    {
        $tot = new MqopsTot();
        $tot = $this->setMqopsTot($request, $tot);
        $tot->save();
        $project = $request['project'] ?? null;
        $this->setMqopsTotDetail($request['details'], $tot, $project);
        $docLink = $request['document_link'] ?? [];
        if ($docLink) {
            foreach ($docLink as $value) {
                $totDoc = new MqopsDocument();
                $totDoc = $this->setMqopsTotDocument($tot, $totDoc, $value);
                $totDoc->save();
            }
        }

        return $tot;
    }

    /**
     * Set Mqops Tot details
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function setMqopsTotDetail($details, $tot, $projects = null)
    {
        foreach ($details as $detail) {
            $parentId = $tot->id;
            $projectId = $detail['project_id'];
            $stateId = $detail['state_id'];
            $count = $detail['participant_count'];
            $maleCount = $detail['male_participant_count'];
            $femaleCount = $detail['female_participant_count'];
            $otherCount = $detail['other_participant_count'];

            $detCount = MqopsTotSummaryProject::where('tot_summary_id', $parentId)
                ->where('project_id', $projectId)
                ->where('state_id', $stateId)->count();
            if ($detCount == 0) {
                $det = new MqopsTotSummaryProject();
                $det->tot_summary_id = $parentId;
                $det->project_id = $projectId;
                $det->state_id = $stateId;
                $det->participant_count = $count;
                $det->male_participant_count = $maleCount;
                $det->female_participant_count = $femaleCount;
                $det->other_participant_count = $otherCount;
                $det->save();
            } else {
                $det = MqopsTotSummaryProject::where('tot_summary_id', $parentId)
                    ->where('project_id', $projectId)
                    ->where('state_id', $stateId)
                    ->update([
                        'tot_summary_id' => $parentId,
                        'project_id' => $projectId,
                        'state_id' => $stateId,
                        'participant_count' => $count,
                        'male_participant_count' => $maleCount,
                        'female_participant_count' => $femaleCount,
                        'other_participant_count' => $otherCount
                    ]);
            }
        }

        // $projects = $projectsnew ?? null;
        if ($projects) {
            MqopsTotSummaryProject::where('tot_summary_id', $tot->id)
                ->whereNotIn('project_id', $projects)->delete();
        }
    }

    /**
     * Set Mqops Tot Document
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function setMqopsTotDocument($tot, $totDoc, $docLink)
    {
        $parentId = $tot->id;
        $docType = MqopsDocument::MQOPS_SESSION;

        $totDoc->parent_id = $parentId;
        $totDoc->file = $docLink;
        $totDoc->mqops_type = $docType;
        return $totDoc;
    }

    /**
     * Set Mqops Tot
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function setMqopsTot($request, $tot)
    {
        $tot->user_id = $request['user_id'] ?? null;
        $tot->mode = isset($request['mode']) ? (($request['mode'] != null) ? $request['mode'] : null) : null;
        $tot->venue_tot = $request['venue_tot'] ?? null;
        $tot->ecosystem_id =
            isset($request['ecosystem_id']) ? (($request['ecosystem_id'] != null) ? $request['ecosystem_id'] : null) : null;
        $tot->other_ecosystem = $request['other_ecosystem'] ?? null;
        $tot->tot_id = $request['tot_id'] ?? null;
        $tot->other_tot = $request['other_tot'] ?? null;
        $tot->start_date = ($request['start_date']) ? Carbon::parse($request['start_date'])->format('Y-m-d') : null;
        $tot->end_date = ($request['end_date']) ? Carbon::parse($request['end_date'])->format('Y-m-d') : null;
        return $tot;
    }

    /**
     * Delete an Mqops tot
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy($tot)
    {
        $tot->delete();
    }

    /**
     * Update Mqops Activity Info
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function update($request, $tot, $user)
    {
        $tot = $this->setMqopsTot($request, $tot, $user);
        $tot->update();

        $project = $request['project'] ?? null;
        $this->setMqopsTotDetail($request['details'], $tot, $project);

        $docLink = $request['document_link'] ?? [];
        MqopsDocument::whereNotIn('file', $docLink)->where('parent_id', $tot->id)
            ->where('mqops_type', MqopsDocument::MQOPS_SESSION)->delete();
        if ($docLink) {
            foreach ($docLink as $value) {
                $existOrNot = MqopsDocument::where('file', $value)->where('parent_id', $tot->id)
                    ->where('mqops_type', MqopsDocument::MQOPS_SESSION)->count();
                if ($existOrNot > 0) {
                    continue;
                }
                $totDoc = new MqopsDocument();
                $totDoc = $this->setMqopsTotDocument($tot, $totDoc, $value);
                $totDoc->save();
            }
        }
        return $tot;
    }

    /**
     * Set Mqops Tot Project Centre
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function getProjectCentre($request)
    {
        $projects = $request['project_id'] ?? [];
        $states = CentreProject::leftJoin('centres', 'centre_project.centre_id', 'centres.id')
            ->leftJoin('states', 'states.id', 'centres.state_id')
            ->leftJoin('projects', 'projects.id', 'centre_project.project_id')
            ->whereIn('centre_project.project_id', $projects)->whereNull('projects.deleted_at')
            ->where('state_id', '!=', '')->whereNotnull('state_id')
            ->select(['states.name as state_name', 'projects.name as project_name', 'centres.state_id', 'centre_project.project_id'])
            ->distinct()->get();
        return $states;
    }

    public function exportTot($request)
    {
        $fileName = "tot_downlods/" . time() . "tot.csv";
        Excel::store(new MqopsTotExport(), $fileName, 's3');
        return generateTempUrl($fileName);
    }
}
