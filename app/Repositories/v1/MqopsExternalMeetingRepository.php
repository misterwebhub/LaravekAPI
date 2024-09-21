<?php

namespace App\Repositories\v1;

use App\Models\MqopsDocument;
use App\Models\MqopsExternalMeeting;
use App\Models\MqopsPartnerType;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\MqopsExternalMeetingUserCustomSort;
use App\Services\MqopsExternalMeetingStateCustomSort;
use App\Services\MqopsExternalMeetingPartnerCustomSort;
use App\Services\MqopsExternalMeetingOrganizationCustomSort;
use App\Exports\MqopsExternalExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
/**
 * Class AccountRepository
 * @package App\Repositories
 */
class MqopsExternalMeetingRepository
{
    /**
     * List all mqops external meeting
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request)
    {
        return QueryBuilder::for(MqopsExternalMeeting::class)
            ->allowedSorts(
                [
                    'start_date', 'end_date',
                    AllowedSort::custom('created_by', new MqopsExternalMeetingUserCustomSort()),
                    AllowedSort::custom('state_name', new MqopsExternalMeetingStateCustomSort()),
                    AllowedSort::custom('partner_type_name', new MqopsExternalMeetingPartnerCustomSort()),
                    AllowedSort::custom('organisation_name', new MqopsExternalMeetingOrganizationCustomSort()),
                ]
            )
            ->when(!auth()->user()->hasRole('super-admin'), function ($q) {
                $q->where('created_by', auth()->user()->id);
            })
            ->latest()
            ->paginate($request['limit'] ?? null);
    }

    /**
     * Create a new mqops external meeting
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request, $user)
    {
        $mqopsExternalMeeting = new MqopsExternalMeeting();
        $mqopsExternalMeeting = $this->setMqopsExternalMeeting($request, $mqopsExternalMeeting);
        $mqopsExternalMeeting->save();
        $mqopsExternalMeeting = $this->setMqopsExternalMeetingUser($request, $mqopsExternalMeeting);
        $files = array_filter(isset($request['files']) ? (($request['files']) ?: []) : []);
        if ($files) {
            foreach ($files as $value) {
                $mqopsDocuments = new MqopsDocument();
                $mqopsDocuments = $this->setMqopsDocument($mqopsDocuments, $mqopsExternalMeeting, $value);
                $mqopsDocuments->save();
            }
        }
        $mqopsExternalMeeting->username = $user->name;
        return $mqopsExternalMeeting;
    }

    /**
     * Delete mqops external meeting
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy($mqopsExternalMeeting)
    {
        $mqopsExternalMeeting->users()->detach();
        $mqopsExternalMeeting->mqopsDocuments()->delete();
        $mqopsExternalMeeting->delete();
    }

    /**
     * Update mqops external meeting Info
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function update($request, $mqopsExternalMeeting, $user)
    {
        $mqopsExternalMeeting = $this->setMqopsExternalMeeting($request, $mqopsExternalMeeting);
        $mqopsExternalMeeting->update();
        $mqopsExternalMeetingUser = $this->setMqopsExternalMeetingUser($request, $mqopsExternalMeeting);
        $files = array_filter(isset($request['files']) ? (($request['files']) ?: []) : []);
        MqopsDocument::whereNotIn('file', $files)->where('parent_id', $mqopsExternalMeeting->id)
            ->where('mqops_type', MqopsDocument::TYPE_EXTERNAL_MEETING)->delete();
        if ($files) {
            foreach ($files as $value) {
                $existOrNot = MqopsDocument::where('file', $value)->where('parent_id', $mqopsExternalMeeting->id)
                    ->where('mqops_type', MqopsDocument::TYPE_EXTERNAL_MEETING)->count();
                if ($existOrNot > 0) {
                    continue;
                }
                $mqopsDocuments = new MqopsDocument();
                $mqopsDocuments = $this->setMqopsDocument($mqopsDocuments, $mqopsExternalMeeting, $value);
                $mqopsDocuments->save();
            }
        }

        return $mqopsExternalMeeting;
    }

    /**
     * Set mqopsExternalMeeting Data
     * @param mixed $request
     *
     * @return [collection]
     */
    private function setMqopsExternalMeeting($request, $mqopsExternalMeeting)
    {
        $mqopsExternalMeeting->start_date = isset($request['start_date']) ? (($request['start_date']) ?: null) : null;
        $mqopsExternalMeeting->end_date = isset($request['end_date']) ? (($request['end_date']) ?: null) : null;
        $mqopsExternalMeeting->state_id = isset($request['state']) ? (($request['state']) ?: null) : null;
        $mqopsExternalMeeting->summary = isset($request['summary']) ? (($request['summary']) ?: null) : null;
        $mqopsExternalMeeting->contact_person = isset($request['contact_person']) ? (
            ($request['contact_person']) ?: null) : null;
        $mqopsExternalMeeting->contact_person_designation = isset($request['designation']) ? (
            ($request['designation']) ?: null) : null;
        $mqopsExternalMeeting->contact_people_count = isset($request['contact_people_count']) ? (
            ($request['contact_people_count']) ?: null) : null;
        $mqopsExternalMeeting->mqops_partner_type_id  = isset($request['partner_type']) ? (
            ($request['partner_type']) ?: null) : null;
        $partnerType = $request['partner_type'];
        $partnerTypeName = MqopsPartnerType::where("id", $partnerType)->first();
        if ($partnerTypeName->name == MqopsExternalMeeting::TYPE_OTHERS) {
            $mqopsExternalMeeting->org_name = isset($request['org_name']) ? (
                ($request['org_name']) ?: null) : null;
            $mqopsExternalMeeting->organisation_id = null;
        } else {
            $mqopsExternalMeeting->organisation_id = isset($request['organisation']) ? (
                ($request['organisation']) ?: null) : null;
            $mqopsExternalMeeting->org_name = null;
        }
        return $mqopsExternalMeeting;
    }

    /**
     * Set mqopsExternalMeetingUser Data
     * @param mixed $request
     *
     * @return [collection]
     */
    private function setMqopsExternalMeetingUser($request, $mqopsExternalMeeting)
    {
        $teamMembers = $request['team_members'] ?? null;
        if (!empty($teamMembers)) {
            $mqopsExternalMeeting->users()->sync($teamMembers);
        }
        return $mqopsExternalMeeting;
    }

    /**
     * Set mqopsDocument Data
     * @param mixed $request
     *
     * @return [collection]
     */
    private function setMqopsDocument($mqopsDocument, $mqopsExternalMeeting, $value)
    {
        $mqopsDocument->parent_id = $mqopsExternalMeeting->id;
        $mqopsDocument->mqops_type = MqopsDocument::TYPE_EXTERNAL_MEETING;
        $mqopsDocument->file = $value;
        return $mqopsDocument;
    }

    public function exportExternalMeeting($request)
    {
        $fileName = "external_meeting_downlods/" . time() . "external_meeting.csv";
        Excel::store(new MqopsExternalExport(), $fileName, 's3');
        return generateTempUrl($fileName);
    }
}
