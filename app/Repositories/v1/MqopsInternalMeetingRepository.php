<?php

namespace App\Repositories\v1;

use App\Models\MqopsInternalMeeting;
use App\Models\MqopsDocument;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\MqopsInternalMeetingUserCustomSort;
use App\Services\MqopsInternalMeetingStateCustomSort;
use App\Exports\MqopsInternalExport;
use Maatwebsite\Excel\Facades\Excel;

class MqopsInternalMeetingRepository
{
    /**
     * List all MqopsInternalMeeting
     * @param mixed $request
     * @return [type]
     */
    public function index($request)
    {
        $mqopsInternalMeeting = QueryBuilder::for(MqopsInternalMeeting::class)
            ->allowedSorts(
                [
                    'start_date', 'end_date',
                    AllowedSort::custom('created_by', new MqopsInternalMeetingUserCustomSort()),
                    AllowedSort::custom('state_name', new MqopsInternalMeetingStateCustomSort()),
                ]
            )
            ->when(!auth()->user()->hasRole('super-admin'), function ($q) {
                $q->where('created_by', auth()->user()->id);
            })
            ->latest()
            ->paginate($request['limit'] ?? null);
        return $mqopsInternalMeeting;
    }

    /**
     * Create a new MqopsInternalMeeting
     * @param mixed $request
     * @return [type]
     */
    public function store($request, $user)
    {
        $mqopsInternalMeeting = new MqopsInternalMeeting();
        $mqopsInternalMeeting = $this->setInternal($request, $mqopsInternalMeeting);
        $mqopsInternalMeeting->created_by = $user->id;
        $mqopsInternalMeeting->save();
        $mqopsInternalMeeting = $this->setMqopsInternalMeetingUser($request, $mqopsInternalMeeting);

        $files = array_filter(isset($request['files']) ? (($request['files']) ?: []) : []);
        if ($files) {
            foreach ($files as $file) {
                $mqopsDocument = new MqopsDocument();
                $mqopsDocument = $this->setMqopsInternalMeetingDocument($mqopsInternalMeeting, $mqopsDocument, $file);
                $mqopsDocument->save();
            }
        }
        $mqopsInternalMeeting->username = $user->name;
        return $mqopsInternalMeeting;
    }

    /**
     * Update MqopsInternalMeeting
     * @param mixed $request
     * @param mixed $mqopsInternalMeeting
     * @return [json]
     */

    public function update($request, $mqopsInternalMeeting, $user)
    {

        $mqopsInternalMeeting = $this->setInternal($request, $mqopsInternalMeeting);
        $mqopsInternalMeeting->updated_by = $user->id;
        $mqopsInternalMeeting->update();
        $mqopsInternalMeeting = $this->setMqopsInternalMeetingUser($request, $mqopsInternalMeeting);
        $files = array_filter(isset($request['files']) ? (($request['files']) ?: []) : []);
        MqopsDocument::whereNotIn('file', $files)->where('parent_id', $mqopsInternalMeeting->id)
            ->where('mqops_type', MqopsDocument::TYPE_INTERNAL_MEETING)->delete();
        if ($files) {
            foreach ($files as $file) {
                $existOrNot = MqopsDocument::where('file', $file)->where('parent_id', $mqopsInternalMeeting->id)
                    ->where('mqops_type', MqopsDocument::TYPE_INTERNAL_MEETING)->count();
                if ($existOrNot > 0) {
                    continue;
                }
                $mqopsDocument = new MqopsDocument();
                $mqopsDocument = $this->setMqopsInternalMeetingDocument($mqopsInternalMeeting, $mqopsDocument, $file);
                $mqopsDocument->save();
            }
        }
        return $mqopsInternalMeeting;
    }


    /**
     * Set MqopsInternalMeeting
     * @param mixed $request
     * @param mixed $mqopsInternalMeeting
     * @return [collection]
     */
    private function setInternal($request, $mqopsInternalMeeting)
    {

        $mqopsInternalMeeting->start_date = $request['start_date'];
        $mqopsInternalMeeting->end_date = $request['end_date'];
        $mqopsInternalMeeting->state_id = $request['state'];
        $mqopsInternalMeeting->summary = isset($request['summary']) ? (($request['summary']) ?: null) : null;

        return $mqopsInternalMeeting;
    }

    /**
     * Set MqopsInternalMeetingUser Data
     * @param mixed $request
     * @return [collection]
     */
    private function setMqopsInternalMeetingUser($request, $mqopsInternalMeeting)
    {
        $teamMembers = $request['team_members'] ?? null;
        if (!empty($teamMembers)) {
            $mqopsInternalMeeting->teamMembers()->sync($teamMembers);
        }
        return $mqopsInternalMeeting;
    }

    /**
     * Set mqopsDocument Data
     * @param mixed $request
     * @return [collection]
     */

    public function setMqopsInternalMeetingDocument($mqopsInternalMeeting, $mqopsDocument, $file)
    {
        $parentId = $mqopsInternalMeeting->id;
        $mqopsDocumentType = MqopsDocument::TYPE_INTERNAL_MEETING;

        $mqopsDocument->parent_id = $parentId;
        $mqopsDocument->file = $file;
        $mqopsDocument->mqops_type = $mqopsDocumentType;
        return $mqopsDocument;
    }

    /**
     * Delete MqopsInternalMeeting
     * @param mixed $mqopsinternalmeeting
     * @return [type]
     */
    public function destroy($mqopsInternalMeeting)
    {
        $mqopsInternalMeeting->delete();
    }

    public function exportInternalMeeting($request)
    {
        $fileName = "internal_meeting_downlods/" . time() . "internal_meeting.csv";
        Excel::store(new MqopsInternalExport(), $fileName, 's3');
        return generateTempUrl($fileName);
    }
}
