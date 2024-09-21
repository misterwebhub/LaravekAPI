<?php

namespace App\Repositories\v1;

use App\Models\MqopsSession;
use App\Models\MqopsDocument;
use App\Models\Project;
use App\Models\CentreProject;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\MqopsSessionUserCustomSort;
use App\Services\MqopsSessionCentreCustomSort;
use App\Services\MqopsSessionStateCustomSort;
use App\Services\MqopsSessionMediumCustomSort;
use App\Services\MqopsSessionTypeCustomSort;
use Carbon\Carbon;
use App\Exports\MqopsSessionExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class MqopsSessionRepository
 * @package App\Repositories
 */
class MqopsSessionRepository
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
        return QueryBuilder::for(MqopsSession::class)
            ->allowedSorts(
                [
                    'start_date', 'end_date',
                    AllowedSort::custom('user_name', new MqopsSessionUserCustomSort()),
                    AllowedSort::custom('centre_type_name', new MqopsSessionCentreCustomSort()),
                    AllowedSort::custom('state_name', new MqopsSessionStateCustomSort()),
                    AllowedSort::custom('mqops_activity_medium_name', new MqopsSessionMediumCustomSort()),
                    AllowedSort::custom('mqops_activity_type_name', new MqopsSessionTypeCustomSort()),
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
     * Create a new Mqops Session
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request, $user)
    {
        $session = new MqopsSession();
        $session = $this->setMqopsSession($request, $session, $user);
        $session->save();

        $this->setMqopsSessionCentre($request, $session);
        $docLink = $request['document_link'] ?? [];
        if ($docLink) {
            foreach ($docLink as $value) {
                $sessionDoc = new MqopsDocument();
                $sessionDoc = $this->setMqopsSessionDocument($session, $sessionDoc, $value);
                $sessionDoc->save();
            }
        }

        return $session;
    }

    /**
     * Set Mqops Session Document
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function setMqopsSessionDocument($session, $sessionDoc, $docLink)
    {
        $parentId = $session->id;
        $docType = MqopsDocument::MQOPS_SESSION;

        $sessionDoc->parent_id = $parentId;
        $sessionDoc->file = $docLink;
        $sessionDoc->mqops_type = $docType;
        return $sessionDoc;
    }

    /**
     * Set Mqops Session
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function setMqopsSession($request, $session, $user)
    {
        if (isset($request['support_for_app']) && is_array($request['support_for_app'])) {
            $support_for_app = implode(",", $request['support_for_app']);
            $request['support_for_app'] = $support_for_app;
        } else {
            $request['support_for_app'] = null;
        }

        if (isset($request['support_for']) && is_array($request['support_for'])) {
            $support_for = implode(",", $request['support_for']);
            $request['support_for'] = $support_for;
        } else {
            $request['support_for'] = null;
        }

        if (isset($request['have_resources']) && is_array($request['have_resources'])) {
            $have_resources = implode(",", $request['have_resources']);
            $request['have_resources'] = $have_resources;
        } else {
            $request['have_resources'] = null;
        }

        $session->user_id = $user->id;
        $session->session_type_id = $request['session_type_id'];
        $session->state_id = $request['state_id'] ?? null;
        $session->mqops_activity_medium_id = $request['mqops_activity_medium_id'];
        $session->bootcamp_type_id = (isset($request['bootcamp_type_id']) && ($request['bootcamp_type_id'] != null)) ? $request['bootcamp_type_id'] : null;
        $session->other_session_details = $request['other_session_details'] ?? null;
        $session->centre_type_id = (isset($request['centre_type_id']) && ($request['centre_type_id'] != null)) ? $request['centre_type_id'] : null;
        $session->centre_id = (isset($request['centre_id']) && ($request['centre_id'] != null)) ? $request['centre_id'] : null;
        $session->project_id = (isset($request['project_id']) && ($request['project_id'] != null)) ? $request['project_id'] : null;
        $session->phase_id = (isset($request['phase_id']) && ($request['phase_id'] != null)) ? $request['phase_id'] : null;
        $session->start_date = ($request['start_date']) ? Carbon::parse($request['start_date'])->format('Y-m-d') : null;
        $session->end_date = ($request['end_date']) ? Carbon::parse($request['end_date'])->format('Y-m-d') : null;
        $session->duration = $request['duration'] ?? null;
        $session->company_name = $request['company_name'] ?? null;
        $session->ext_person_name = $request['ext_person_name'] ?? null;
        $session->guest_type_id = (isset($request['guest_type_id']) && ($request['guest_type_id'] != null)) ? $request['guest_type_id'] : null;
        $session->volunteer_count = isset($request['volunteer_count']) ? (($request['volunteer_count']) ? $request['volunteer_count']: null) : null;
        $session->session_details = $request['session_details'] ?? null;
        $session->participant_count = isset($request['participant_count']) ? (($request['participant_count']) ? $request['participant_count'] : 0) : null;
        $session->male_participant_count = isset($request['male_participant_count']) ? (($request['male_participant_count']) ? $request['male_participant_count'] : 0) : null;
        $session->female_participant_count = isset($request['female_participant_count']) ? (($request['female_participant_count']) ? $request['female_participant_count'] : 0) : null;
        $session->other_participant_count = isset($request['other_participant_count']) ? (($request['other_participant_count']) ? $request['other_participant_count'] : 0) : null;
        $session->topics_covered = $request['topics_covered'] ?? null;
        $session->es_trainer_present = isset($request['es_trainer_present']) ? (($request['es_trainer_present'] != null) ? $request['es_trainer_present'] : null) : null;
        $session->career_club_role = isset($request['career_club_role']) ? (($request['career_club_role'] != null) ? $request['career_club_role'] : null) : null;
        $session->require_more_support = isset($request['require_more_support']) ? (($request['require_more_support'] != null) ? $request['require_more_support'] : null) : null;
        $session->support_for = $request['support_for'] ?? null;
        $session->mobile_access_count = isset($request['mobile_access_count']) ? (($request['mobile_access_count'] != null) ? $request['mobile_access_count'] : null) : null;
        $session->insight_from_learners = isset($request['insight_from_learners']) ? (($request['insight_from_learners'] != null) ? $request['insight_from_learners'] : null) : null;
        $session->need_support_explore = isset($request['need_support_explore']) ? (($request['need_support_explore'] != null) ? $request['need_support_explore'] : null) : null;
        $session->organised_by_institution = isset($request['organised_by_institution']) ? (($request['organised_by_institution'] != null) ? $request['organised_by_institution'] : null) : null;
        $session->support_for_app = $request['support_for_app'] ?? null;
        $session->any_practice = $request['any_practice'] ?? null;
        $session->key_highlights = $request['key_highlights'] ?? null;
        $session->have_resources = $request['have_resources'] ?? null;
        $session->others_institution = $request['others_institution'] ?? null;
        $session->others_support = $request['others_support'] ?? null;
        $session->others_support_app = $request['others_support_app'] ?? null;
        $session->leaders_role = isset($request['leaders_role']) ? (($request['leaders_role'] != null) ? $request['leaders_role'] : null) : null;
        return $session;
    }

    /**
     * Delete an Mqops session
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy($session)
    {
        $session->delete();
    }

    /**
     * Update Mqops Activity Info
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function update($request, $session, $user)
    {
        $session = $this->setMqopsSession($request, $session, $user);
        $session->update();

        $this->setMqopsSessionCentre($request, $session);
        $docLink = $request['document_link'] ?? [];
        MqopsDocument::whereNotIn('file', $docLink)->where('parent_id', $session->id)
            ->where('mqops_type', MqopsDocument::MQOPS_SESSION)->delete();
        if ($docLink) {
            foreach ($docLink as $value) {
                $existOrNot = MqopsDocument::where('file', $value)->where('parent_id', $session->id)
                    ->where('mqops_type', MqopsDocument::MQOPS_SESSION)->count();
                if ($existOrNot > 0) {
                    continue;
                }
                $sessionDoc = new MqopsDocument();
                $sessionDoc = $this->setMqopsSessionDocument($session, $sessionDoc, $value);
                $sessionDoc->save();
            }
        }
        return $session;
    }

    /**
     * Set Mqops session projects
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */

    public function projectList($request)
    {
        $centres = $request['centre_id'] ?? null;
        $centreProjects = CentreProject::whereIn('centre_id', $centres)->get()->pluck('project_id')->toArray();
        $projects = Project::whereIn('id', $centreProjects)->get();
        return $projects;
    }


    public function exportSession($request)
    {
        $fileName = "session_downlods/" . time() . "session.csv";
        Excel::store(new MqopsSessionExport(), $fileName, 's3');
        return generateTempUrl($fileName);
    }


    /**
     * Set Mqops Session Centre
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function setMqopsSessionCentre($request, $activity)
    {
        $activity->centres()->detach();

        $centre = $request['centre'];
        foreach ($centre as $key => $centreId) {
            $activity->centres()->attach($centreId);
        }
        return $activity;
    }

}
