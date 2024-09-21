<?php

namespace App\Repositories\v1;

use App\Models\MqopsDocument;
use App\Models\MqopsCentreVisit;
use Carbon\Carbon;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\MqopsCentreUserCustomSort;
use App\Services\MqopsCentreTypeCustomSort;
use App\Services\MqopsCentreStateCustomSort;
use App\Exports\MqopsCentreExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class MqopsCentreVisitRepository
 * @package App\Repositories
 */
class MqopsCentreVisitRepository
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
        return QueryBuilder::for(MqopsCentreVisit::class)
            ->allowedSorts(
                [
                    'start_date', 'end_date', 'infrastructure', 'publicity_material',
                    'placement_issue', 'quest_content', 'rating', 'immediate_action',
                    'student_data', 'meet_authority', 'trainer_issues', 'mobilization_issues',
                    'student_count', 'attendance_issues', 'digital_lesson',
                    AllowedSort::custom('user_name', new MqopsCentreUserCustomSort()),
                    AllowedSort::custom('centre_type_name', new MqopsCentreTypeCustomSort()),
                    AllowedSort::custom('state_name', new MqopsCentreStateCustomSort()),
                ]
            )
            ->when(!auth()->user()->hasRole('super-admin'), function ($q) {
                $q->where('user_id', auth()->user()->id);
            })
            ->latest()
            ->paginate($request['limit'] ?? null);
    }

    /**
     * Create a new mqops centre visit
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request, $user)
    {
        $mqopsCentreVisit = new MqopsCentreVisit();
        $mqopsCentreVisit->user_id = $user->id;
        $mqopsCentreVisit = $this->setMqopsCentreVisit($request, $mqopsCentreVisit);
        $mqopsCentreVisit->save();
        $files = array_filter(isset($request['files']) ? (($request['files']) ?: []) : []);
        if ($files) {
            foreach ($files as $value) {
                $mqopsDocuments = new MqopsDocument();
                $mqopsDocuments = $this->setMqopsDocument($mqopsDocuments, $mqopsCentreVisit, $value);
                $mqopsDocuments->save();
            }
        }
        $mqopsCentreVisit = $this->setMqopsCentreVisitUser($request, $mqopsCentreVisit);
        $mqopsCentreVisit = $this->setCentreMqopsCentreVisit($request, $mqopsCentreVisit);
        $mqopsCentreVisit->username = $user->name;
        return $mqopsCentreVisit;
    }



    /**
     * Delete mqops external meeting
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy($mqopsCentreVisit)
    {
        $mqopsCentreVisit->users()->detach();
        $mqopsCentreVisit->centres()->detach();
        $mqopsCentreVisit->mqopsDocuments()->delete();
        $mqopsCentreVisit->delete();
    }

    /**
     * Update mqops external meeting Info
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function update($request, $mqopsCentreVisit)
    {
        $mqopsCentreVisit = $this->setMqopsCentreVisit($request, $mqopsCentreVisit);
        $mqopsCentreVisit->update();
        $mqopsCentreVisitUser = $this->setMqopsCentreVisitUser($request, $mqopsCentreVisit);
        $centreMqopsCentreVisit = $this->setCentreMqopsCentreVisit($request, $mqopsCentreVisit);
        $files = array_filter(isset($request['files']) ? (($request['files']) ?: []) : []);
        MqopsDocument::whereNotIn('file', $files)->where('parent_id', $mqopsCentreVisit->id)
            ->where('mqops_type', MqopsDocument::MQOPS_CENTRE_VISIT)->delete();
        if ($files) {
            foreach ($files as $value) {
                $existOrNot = MqopsDocument::where('file', $value)->where('parent_id', $mqopsCentreVisit->id)
                    ->where('mqops_type', MqopsDocument::MQOPS_CENTRE_VISIT)->count();
                if ($existOrNot > 0) {
                    continue;
                }
                $mqopsDocuments = new MqopsDocument();
                $mqopsDocuments = $this->setMqopsDocument($mqopsDocuments, $mqopsCentreVisit, $value);
                $mqopsDocuments->save();
            }
        }

        return $mqopsCentreVisit;
    }

    /**
     * Set mqopsCentreVisit Data
     * @param mixed $request
     *
     * @return [collection]
     */
    private function setMqopsCentreVisit($request, $mqopsCentreVisit)
    {
        $mqopsCentreVisit->start_date = Carbon::parse($request['start_date'])->format('Y-m-d');
        $mqopsCentreVisit->end_date = Carbon::parse($request['end_date'])->format('Y-m-d');
        $mqopsCentreVisit->state_id = $request['state'];
        $mqopsCentreVisit->district_id = $request['district'];
        $mqopsCentreVisit->centre_type_id = $request['centre_type_id'];
        $mqopsCentreVisit->visit_purpose = $request['visit_purpose'];
        $mqopsCentreVisit->infrastructure = $request['infrastructure'];
        $mqopsCentreVisit->infrastructure_issues = isset($request['infrastructure_issues']) ? (
            ($request['infrastructure_issues'] != null) ? $request['infrastructure_issues']: null) : null;
        $mqopsCentreVisit->good_practice = isset($request['good_practice']) ? (
            ($request['good_practice'] != null) ? $request['good_practice']: null) : null;
        $mqopsCentreVisit->publicity_material = $request['publicity_material'];
        $mqopsCentreVisit->quest_content = $request['quest_content'];
        $mqopsCentreVisit->placement_issue = $request['placement_issue'];
        $mqopsCentreVisit->immediate_action = $request['immediate_action'];
        $mqopsCentreVisit->feedback = $request['feedback'] ?? null;
        $mqopsCentreVisit->rating = $request['rating'];
        $mqopsCentreVisit->student_data = isset($request['student_data']) ? (
            ($request['student_data'] != null) ? $request['student_data'] : null) : null;
        $mqopsCentreVisit->meet_authority = isset($request['meet_authority']) ? (
            ($request['meet_authority'] != null) ? $request['meet_authority']: null) : null;
        $mqopsCentreVisit->trainer_issues = isset($request['trainer_issues']) ? (
            ($request['trainer_issues'] != null) ? $request['trainer_issues']: null) : null;
        $mqopsCentreVisit->mobilization_issues = isset($request['mobilization_issues']) ? (
            ($request['mobilization_issues'] != null) ? $request['mobilization_issues']: null) : null;
        $mqopsCentreVisit->student_count = isset($request['student_count']) ? (
            ($request['student_count']) ?: null) : null;
        $mqopsCentreVisit->attendance_issues = isset($request['attendance_issues']) ? (
            ($request['attendance_issues'] != null) ? $request['attendance_issues']: null) : null;
        $mqopsCentreVisit->digital_lesson = isset($request['digital_lesson']) ? (
            ($request['digital_lesson'] != null) ? $request['digital_lesson']: null) : null;
       
        return $mqopsCentreVisit;
    }

    /**
     * Set mqopsCentreVisitUser Data
     * @param mixed $request
     *
     * @return [collection]
     */
    private function setMqopsCentreVisitUser($request, $mqopsCentreVisit)
    {
        $teamMembers = $request['team_members'] ?? null;
        if (!empty($teamMembers)) {
            $mqopsCentreVisit->users()->sync($teamMembers);
        }
        return $mqopsCentreVisit;
    }

    /**
     * Set mqopsCentreVisitUser Data
     * @param mixed $request
     *
     * @return [collection]
     */
    private function setCentreMqopsCentreVisit($request, $mqopsCentreVisit)
    {
        $centres = array_filter($request['centres']);
        $mqopsCentreVisit->centres()->sync($centres);
        return $mqopsCentreVisit;
    }

    /**
     * Set mqopsDocument Data
     * @param mixed $request
     *
     * @return [collection]
     */
    private function setMqopsDocument($mqopsDocument, $mqopsCentreVisit, $value)
    {
        $mqopsDocument->mqops_type = MqopsDocument::MQOPS_CENTRE_VISIT;
        $mqopsDocument->file = $value;
        $mqopsDocument->parent_id = $mqopsCentreVisit->id;
        return $mqopsDocument;
    }

    public function exportCentre($request)
    {
        $fileName = "external_centre_downlods/" . time() . "centre visit.csv";
        Excel::store(new MqopsCentreExport(), $fileName, 's3');
        return generateTempUrl($fileName);
    }
}
