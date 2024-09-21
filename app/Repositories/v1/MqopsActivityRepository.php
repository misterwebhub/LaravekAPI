<?php

namespace App\Repositories\v1;

use App\Models\MqopsActivity;
use App\Models\MqopsDocument;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Carbon\Carbon;
use DB;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\MqopsActivityUserCustomSort;
use App\Services\MqopsActivityCentreCustomSort;
use App\Services\MqopsActivityStateCustomSort;
use App\Services\MqopsActivityMediumCustomSort;
use App\Services\MqopsActivityTypeCustomSort;

/**
 * Class MqopsActivityRepository
 * @package App\Repositories
 */
class MqopsActivityRepository
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
        return QueryBuilder::for(MqopsActivity::class)
            ->allowedSorts(
                [
                    'session_start_date', 'session_end_date',
                    AllowedSort::custom('user_name', new MqopsActivityUserCustomSort()),
                    AllowedSort::custom('centre_type_name', new MqopsActivityCentreCustomSort()),
                    AllowedSort::custom('state_name', new MqopsActivityStateCustomSort()),
                    AllowedSort::custom('mqops_activity_medium_name', new MqopsActivityMediumCustomSort()),
                    AllowedSort::custom('mqops_activity_type_name', new MqopsActivityTypeCustomSort()),
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
     * Create a new Mqops Activity
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request, $user)
    {
        $activity = new MqopsActivity();
        $activity = $this->setMqopsActivity($request, $activity, $user);
        $activity->save();

        $this->setMqopsActivityCentre($request, $activity);
        $this->setMqopsActivityBatch($request, $activity);
        $docLink = $request['document_link'] ?? null;
        if ($docLink) {
            foreach ($docLink as $value) {
                $activityDoc = new MqopsDocument();
                $activityDoc = $this->setMqopsActivityDocument($activity, $activityDoc, $value);
                $activityDoc->save();
            }
        }

        return $activity;
    }

    /**
     * Delete an Mqops Activity
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy($activity)
    {
        $activity->batches()->detach();
        $activity->centres()->detach();
        $activity->delete();
    }

    /**
     * Update Mqops Activity Info
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function update($request, $activity, $user)
    {
        $activity = $this->setMqopsActivity($request, $activity, $user);
        $activity->update();

        $this->setMqopsActivityCentre($request, $activity);
        $this->setMqopsActivityBatch($request, $activity);
        $docLink = $request['document_link'] ?? null;
        MqopsDocument::whereNotIn('file', $docLink)->where('parent_id', $activity->id)
            ->where('mqops_type', MqopsDocument::MQOPS_ACTIVITY)->delete();
        if ($docLink) {
            foreach ($docLink as $value) {
                $existOrNot = MqopsDocument::where('file', $value)->where('parent_id', $activity->id)
                    ->where('mqops_type', MqopsDocument::MQOPS_ACTIVITY)->count();
                if ($existOrNot > 0) {
                    continue;
                }
                $activityDoc = new MqopsDocument();
                $activityDoc = $this->setMqopsActivityDocument($activity, $activityDoc, $value);
                $activityDoc->save();
            }
        }
        return $activity;
    }

    /**
     * Set Mqops Activity
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function setMqopsActivity($request, $activity, $user)
    {
        $activity->user_id = $user->id;
        $activity->centre_type_id = $request['centre_type_id'];
        $activity->state_id = $request['state_id'];
        $activity->mqops_activity_medium_id = $request['mqops_activity_medium_id'];
        $activity->mqops_activity_type_id = $request['mqops_activity_type_id'];
        $activity->session_name = $request['session_name'] ?? "";
        $activity->session_start_date = Carbon::parse($request['session_start_date'])->format('Y-m-d');
        $activity->session_end_date = Carbon::parse($request['session_end_date'])->format('Y-m-d');

        $activity->participants_count = null;
        if (isset($request['participants_count'])) {
            $activity->participants_count = ($request['participants_count'] != '') ? $request['participants_count'] : null;
        }
        $activity->female_participants_count = null;
        if (isset($request['female_participants_count'])) {
            $activity->female_participants_count = ($request['female_participants_count'] != '') ? $request['female_participants_count'] : null;
        }
        $activity->male_participants_count = null;
        if (isset($request['male_participants_count'])) {
            $activity->male_participants_count = ($request['male_participants_count'] != '') ? $request['male_participants_count'] : null;
        }
        $activity->other_participants_count = null;
        if (isset($request['other_participants_count'])) {
            $activity->other_participants_count = ($request['other_participants_count'] != '') ? $request['other_participants_count'] : null;
        }

        $activity->parents_count = null;
        if (isset($request['parents_count'])) {
            $activity->parents_count = ($request['parents_count'] != '') ? $request['parents_count'] : null;
        }
        $activity->female_parents_count = null;
        if (isset($request['female_parents_count'])) {
            $activity->female_parents_count = ($request['female_parents_count'] != '') ? $request['female_parents_count'] : null;
        }
        $activity->male_parents_count = null;
        if (isset($request['male_parents_count'])) {
            $activity->male_parents_count = ($request['male_parents_count'] != '') ? $request['male_parents_count'] : null;
        }
        $activity->other_parents_count = null;
        if (isset($request['other_parents_count'])) {
            $activity->other_parents_count = ($request['other_parents_count'] != '') ? $request['other_parents_count'] : null;
        }

        $activity->company = $request['company'] ?? null;
        $activity->ext_person_det = $request['ext_person_det'] ?? null;
        $activity->comapny_person_name = $request['comapny_person_name'] ?? null;
        $activity->company_person_designation = $request['company_person_designation'] ?? null;
        $activity->duration = $request['duration'] ?? null;
        $activity->feedback = $request['feedback'] ?? "";
        $activity->support_of_any_quest = $request['support_of_any_quest'] ?? null;
        $activity->which_team_supported = $request['which_team_supported'] ?? null;
        $activity->tenant_id = getTenant();
        return $activity;
    }

    /**
     * Set Mqops Activity Batch
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function setMqopsActivityBatch($request, $activity)
    {
        $activity->batches()->detach();
        $batch = $request['batch'] ?? [];
        if($batch)
        {
            foreach ($batch as $key => $batchId) {
                $activity->batches()->syncWithoutDetaching($batchId);
            }
        }
        return $activity;
    }

    /**
     * Set Mqops Activity Centre
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function setMqopsActivityCentre($request, $activity)
    {
        $activity->centres()->detach();

        $centre = $request['centre'];
        foreach ($centre as $key => $centreId) {
            $activity->centres()->attach($centreId);
        }
        return $activity;
    }

    /**
     * Set Mqops Activity Document
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function setMqopsActivityDocument($activity, $activityDoc, $docLink)
    {
        $parentId = $activity->id;
        $docType = MqopsDocument::MQOPS_ACTIVITY;

        $activityDoc->parent_id = $parentId;
        $activityDoc->file = $docLink;
        $activityDoc->mqops_type = $docType;
        return $activityDoc;
    }
}
