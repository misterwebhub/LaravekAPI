<?php

namespace App\Repositories\v1;

use App\Models\LearningActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Centre;
use App\Models\Batch;
use App\Models\Community;
use App\Models\PointUser;
use App\Models\UserResource;
use App\Models\Phase;
use Illuminate\Validation\ValidationException;
use App\Models\StudentDetail;
use Carbon\Carbon;

/**
 * Class CentreMergeRepository
 * @package App\Repositories
 */
class CentreMergeRepository
{
    /**
     * Centre Merge
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function centreMerge($request)
    {
        DB::beginTransaction();
        $fromCentreId = $request['from_centre'];
        $toCentreId = $request['to_centre'];
        $fromCentreDetails = Centre::where('id', $fromCentreId)->first();
        $toCentreDetails = Centre::where('id', $toCentreId)->first();
        $toOrgId = $toCentreDetails->organisation_id;
        $fromAddress = $request['from_address'];
        $history = [];
        $history['from'] = $fromCentreId;
        $history['to'] = $toCentreId;
        $studentArray = [];
        $aluminiArray = [];
        $batchIdArray = [];
        $facilitatorArray = [];
        $batches = $request['batch'];
        $this->checkAllFieldsEmpty($request);
        // To update subjects from, From centre to To centre

        $subjectsArray = DB::table('centre_subject')->where('centre_id', $fromCentreId)->pluck('subject_id');
        if (count($subjectsArray)) {
            $this->subjectUpdate($toCentreId, $fromCentreId, $subjectsArray);
        }
        if ($request['address_checkbox'] == 1) {
            $this->addressUpdate($fromAddress, $toCentreDetails);
        }
        $this->phaseUpdate($fromCentreId, $toCentreId, $request['student_checkbox']);
        if (count(array_filter($request['project'])) > 0) {
            $projectArray = $request['project'];
            $this->projectUpdate($projectArray, $fromCentreId, $toCentreId);
            $history['project'] = $projectArray;
        }
        if ($request['facilitator_checkbox'] == 1) {
            $history['facilitator'] = $this->facilitatorUpdate(
                $fromCentreDetails,
                $fromCentreId,
                $toCentreId,
                $toOrgId
            );
            $facilitatorArray = $history['facilitator'];
        }
        $toBatchArray = $toCentreDetails->batches->pluck('id')->toArray();
        $toBatchNameArray = $toCentreDetails->batches->pluck('name')->toArray();
        if ($request['batch']['0']['id'] != "") {
            $history['batch'] = $this->batchUpdate(
                $toCentreDetails,
                $batches,
                $toCentreId,
                $toBatchNameArray,
                $fromCentreId,
            );
            $batchIdArray = $history['batch'];
        }
        $batchIdArray = array_merge($batchIdArray, $toBatchArray);
        if ($request['student_checkbox'] == 1) {
            $history['student'] = $this->studentUpdate(
                $fromCentreDetails,
                $fromCentreId,
                $toCentreId,
                $batches,
                $toOrgId,
            );
            $studentArray = $history['student'];
        }
        if ($request['alumini_checkbox'] == 1) {
            $history['alumini'] = $this->aluminiUpdate($fromCentreDetails, $fromCentreId, $toCentreId, $toOrgId);
            $aluminiArray = $history['alumini'];
        }
        if ($request['admin_checkbox'] == 1) {
            $history['admin'] = $this->adminUpdate($fromCentreDetails, $toCentreId, $toOrgId);
        }
        if ($request['community_checkbox'] == 1) {
            $totalstudentArray = $studentArray->merge($aluminiArray);
            $this->communityUpdate($totalstudentArray, $facilitatorArray, $fromCentreId, $toCentreId);
        }
        $fromCentreDetails->delete();
        DB::commit();
        $data['message'] = trans('admin.centre_merge_completed');
        return  $data;
    }

    /**
     * @param $toCentreId
     * @param $fromCentreId
     * @param $subjectsArray
     */
    private function subjectUpdate($toCentreId, $fromCentreId, $subjectsArray)
    {
        DB::table('centre_subject')->whereIn('subject_id', $subjectsArray)
            ->where('centre_id', $toCentreId)->delete();
        DB::table('centre_subject')->whereIn('subject_id', $subjectsArray)
            ->where('centre_id', $fromCentreId)->update(['centre_id' => $toCentreId]);
    }

    /**
     * @param $fromAddress
     * @param $toCentreDetails
     */
    private function addressUpdate($fromAddress, $toCentreDetails)
    {
        if ($fromAddress != "") {
            $toCentreDetails->address = $fromAddress;
            $toCentreDetails->update();
        }
    }

    /**
     * @param $projectArray
     * @param $fromCentreId
     * @param $toCentreId
     */
    private function projectUpdate($projectArray, $fromCentreId, $toCentreId)
    {
        DB::table('centre_project')->whereIn('project_id', $projectArray)
            ->where('centre_id', $toCentreId)->delete();
        DB::table('centre_project')->whereIn('project_id', $projectArray)
            ->where('centre_id', $fromCentreId)->update(['centre_id' => $toCentreId]);
    }

    /**
     * @param $projectArray
     * @param $fromCentreId
     * @param $toCentreId
     */
    private function phaseUpdate($fromCentreId, $toCentreId, $student_checkbox)
    {
        $phasesTo = DB::table('centre_phase')->where('centre_id', $toCentreId)->get();
        foreach ($phasesTo as $phase) {
            $phase1 = Phase::where('id', $phase->phase_id)->first();
            if ($student_checkbox == 1) {
                $studentsTo = DB::table('users')->where('centre_id', $fromCentreId)
                    ->where('created_at', '>=', Carbon::parse($phase1->start_date)->format('Y-m-d 00:00:00'))
                    ->where('created_at', '<=', Carbon::parse($phase1->end_date)->format('Y-m-d 23:59:59'))
                    ->where('deleted_at', null)->get()->pluck('id');
                if ($studentsTo) {
                    $phase1->users()->attach($studentsTo, ['centre_id' => $toCentreId]);
                }
            }
        }
        $phasesFrom = DB::table('centre_phase')->where('centre_id', $fromCentreId)->get();
        foreach ($phasesFrom as $phases) {
            $phase = Phase::where('id', $phases->phase_id)->first();
            DB::table('centre_phase')->where('phase_id', $phase->id)
                ->where('centre_id', $fromCentreId)->update(['centre_id' => $toCentreId]);
            if ($student_checkbox == 1) {
                DB::table('phase_users')->where('phase_id', $phase->id)
                    ->where('centre_id', $fromCentreId)->update(['centre_id' => $toCentreId]);
            }
            $students = DB::table('users')->where('centre_id', $toCentreId)
                ->where('created_at', '>=', Carbon::parse($phase->start_date)->format('Y-m-d 00:00:00'))
                ->where('created_at', '<=', Carbon::parse($phase->end_date)->format('Y-m-d 23:59:59'))
                ->where('deleted_at', null)->get()->pluck('id');
            if ($students) {
                $phase->users()->attach($students, ['centre_id' => $toCentreId]);
            }
        }
    }

    /**
     * @param $fromCentreDetails
     * @param $fromCentreId
     * @param $toCentreId
     * @param $toOrgId
     */
    private function facilitatorUpdate($fromCentreDetails, $fromCentreId, $toCentreId, $toOrgId)
    {
        $fromFacilitators = $fromCentreDetails->facilitators->where('type', User::TYPE_FACILITATOR);

        $facilitatorArray = $fromFacilitators->pluck('id');
        User::whereIn('id', $facilitatorArray)->update(['centre_id' => $toCentreId, 'organisation_id' => $toOrgId]);
        DB::table('centre_user')->whereIn('user_id', $facilitatorArray)
            ->where('centre_id', $toCentreId)->delete();
        DB::table('centre_user')->whereIn('user_id', $facilitatorArray)->where('centre_id', $fromCentreId)
            ->update(['centre_id' => $toCentreId]);
        return $facilitatorArray;
    }

    /**
     * @param $toCentreDetails
     * @param $batches
     * @param $toCentreId
     * @param $toBatchArray
     * @param $fromCentreId
     */
    private function batchUpdate($toCentreDetails, $batches, $toCentreId, $toBatchNameArray, $fromCentreId)
    {
        foreach ($batches as $batch) {
            $batchId = $batch['id'];
            $batchName = $batch['name'];
            $batchIdArray[] = $batchId;
            if (in_array($batchName, $toBatchNameArray)) {
                $message = "Batch with name " . $batchName . " already exists in " . $toCentreDetails->name;
                DB::rollback();
                throw ValidationException::withMessages(['batch' => $message]);
            }
            Batch::where('id', $batchId)->where('centre_id', $fromCentreId)
                ->update(['centre_id' => $toCentreId, 'name' => $batchName]);
        }

        return $batchIdArray;
    }
    /**
     * @param $fromCentreDetails
     * @param $fromCentreId
     * @param $toCentreId
     * @param $batchIdArray
     * @param $toOrgId
     */
    private function studentUpdate($fromCentreDetails, $fromCentreId, $toCentreId, $batchIdArray, $toOrgId)
    {
        $fromStudents = $fromCentreDetails->students->where('type', User::TYPE_STUDENT);
        $studentArray = $fromStudents->pluck('id');
        $batchList = $fromCentreDetails->batches->whereNotIn('id', $batchIdArray)->pluck('id')->toArray();
        if (count($batchList) > 0) {
            StudentDetail::whereIn('batch_id', $batchList)->update(['batch_id' => null]);
        }
        User::whereIn('id', $studentArray)->update(['centre_id' => $toCentreId, 'organisation_id' => $toOrgId]);
        $this->setStudentDetails($studentArray, $fromCentreId, $toCentreId);
        return $studentArray;
    }
    /**
     * @param $fromCentreDetails
     * @param $fromCentreId
     * @param $toCentreId
     * @param $toOrgId
     */
    private function aluminiUpdate($fromCentreDetails, $fromCentreId, $toCentreId, $toOrgId)
    {
        $fromAlumini = $fromCentreDetails->alumnis->where('type', User::TYPE_ALUMNI);
        $aluminiArray = $fromAlumini->pluck('id');
        User::whereIn('id', $aluminiArray)->update(['centre_id' => $toCentreId, 'organisation_id' => $toOrgId]);
        $this->setStudentDetails($aluminiArray, $fromCentreId, $toCentreId);
        return $aluminiArray;
    }
    /**
     * @param $fromCentreDetails
     * @param $toCentreId
     * @param $toOrgId
     */
    private function adminUpdate($fromCentreDetails, $toCentreId, $toOrgId)
    {
        $fromAdmin = $fromCentreDetails->centreHeads;
        $adminArray = $fromAdmin->pluck('id');
        User::whereIn('id', $adminArray)->update(['centre_id' => $toCentreId, 'organisation_id' => $toOrgId]);
        return $adminArray;
    }

    /**
     * @param $totalstudentArray
     * @param $facilitatorArray
     * @param $fromCentreId
     * @param $toCentreId
     */
    private function communityUpdate($totalstudentArray, $facilitatorArray, $fromCentreId, $toCentreId)
    {
        Community::whereIn('user_id', $totalstudentArray)->where('centre_id', $fromCentreId)
            ->update(['centre_id' => $toCentreId]);
        Community::whereIn('user_id', $facilitatorArray)->where('centre_id', $fromCentreId)
            ->update(['centre_id' => $toCentreId]);
    }

    /**
     * @param $studentArray
     * @param $fromCentreId
     * @param $toCentreId
     */
    private function setStudentDetails($studentArray, $fromCentreId, $toCentreId)
    {
        PointUser::whereIn('user_id', $studentArray)->where('centre_id', $fromCentreId)
            ->update(['centre_id' => $toCentreId]);
        LearningActivity::whereIn('user_id', $studentArray)->where('centre_id', $fromCentreId)
            ->update(['centre_id' => $toCentreId]);
        UserResource::whereIn('user_id', $studentArray)->where('centre_id', $fromCentreId)
            ->update(['centre_id' => $toCentreId]);
        DB::table('badge_user')->whereIn('user_id', $studentArray)->where('centre_id', $fromCentreId)
            ->update(['centre_id' => $toCentreId]);
    }

    /**
     * @param $request
     */
    private function checkAllFieldsEmpty($request)
    {
        if (
            $request['address_checkbox'] == 0 && count(array_filter($request['project'])) == 0 &&
            $request['facilitator_checkbox'] == 0 && $request['batch']['0']['id'] == "" &&
            $request['student_checkbox'] == 0  && $request['alumini_checkbox'] == 0 &&
            $request['admin_checkbox'] == 0 && $request['community_checkbox'] == 0
        ) {
            $data['from_centre'] = trans('admin.nothing_merge');
            throw ValidationException::withMessages(
                $data
            );
        }
    }
}
