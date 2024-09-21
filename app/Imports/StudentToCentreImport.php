<?php

namespace App\Imports;

use App\Exports\StudentCentreSuccessErrorExport;
use App\Models\Batch;
use App\Models\Centre;
use App\Models\EducationalQualification;
use App\Models\Placement;
use App\Models\PlacementStatus;
use App\Models\PlacementType;
use App\Models\StudentDetail;
use App\Models\Trade;
use App\Models\Approval;
use App\Models\Sector;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use App\Mail\StudentAdded;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

/**
 * Class StudentToCentreImport
 * @package App\Imports
 */
class StudentToCentreImport implements ToCollection, WithStartRow
{
    public $data;
    public $workingMode = null;
    public $centreType = null;

    protected $centreId;
    protected $approvalConst;

    public function __construct($centreId, $approvalConst)
    {
        $this->centreId = $centreId;
        $this->approvalConst = $approvalConst;
    }
    /**
     *
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $finalResultAll = [];
        $finalResultErrors = [];
        $errorCount = 0;
        foreach ($collection->chunk(10) as $chunk) {
            foreach ($chunk as $row) {
                $finalRes = [];
                $errors = [];
                $userId = null;
                $centreDet = Centre::find($this->centreId);
                if ($centreDet) {
                    $this->workingMode = $centreDet->working_mode;
                    $this->centreType = ($centreDet->centreType->type == 'ngo')?'ngo':'iti';
                }
                $studentDetails = $this->fetchdata($row);
                foreach ($row as $value) {
                    $finalRes[] = $value;
                }
                $statusColumn = count($finalRes);
                if ($row[0]) {
                    $userId = (string)$row[0];
                    $user = User::where('id', $row[0])->first();
                    $errors = $this->validateFields((string)$row[0], $studentDetails);
                } else {
                    $user = new User();
                    $errors = $this->validateFields("", $studentDetails);
                }
                if (empty($errors)) {
                    $studentInformation =
                        $this->studentAddOrUpdate($user, $studentDetails, $row[0], $statusColumn, $finalRes);
                    $finalRes = $studentInformation['finalRes'];
                    $userId = $studentInformation['userId'];
                } else {
                    $errorCount++;
                    $finalRes[$statusColumn] = "Student details fail: " . implode(',', $errors);
                }
                $placementSuccess = 1;
                if (!empty($userId)) {
                    $placementInformation =
                        $this->placementInformation($userId, $finalRes, $placementSuccess, $row, $statusColumn);
                    $finalRes = $placementInformation['finalRes'];
                    $placementSuccess = $placementInformation['placementSuccess'];
                }
                $finalResultAll[] = $finalRes;
                if (!empty($errors) || $placementSuccess == 0) {
                    $finalResultErrors[] = $finalRes;
                }
            }
        }
        $exportData = $this->exportData($finalResultAll, $finalResultErrors, $errorCount, $placementSuccess);
        $this->data = $exportData;
    }

    private function fetchdata($row)
    {
        $studentDetails['student_name'] = trim($row[1]);
        $studentDetails['email'] = trim($row[4]) ?? null;
        $studentDetails['phone'] = trim($row[5]) ?? null;
        $studentDetails['gender'] = config('staticcontent.gender.' .
            trim(ucwords($row[2]))) ?? null;
        $educationDetails = EducationalQualification::where('name', trim($row[7]))->first();
        $studentDetails['educational_qualification_id'] = $educationDetails->id ?? null;
        $maritalStatus = array_search(trim($row[8]), config('staticcontent.maritalStatus'));
        if ($maritalStatus !== false) {
            $studentDetails['marital_status'] = $maritalStatus;
        } else {
            $studentDetails['marital_status'] = null;
        }
        $studentDetails['work_experience'] = config('staticcontent.student_work_experience.' .
            trim($row[9])) ?? null;
        $studentDetails['guardian_income'] = config('staticcontent.student_guardian_income.' .
            trim($row[13])) ?? null;
        $studentDetails['guardian_occupation'] = trim($row[14]);
        if (trim($row[3]) != "") {
            if (Carbon::hasFormat($row[3], 'd-m-Y') == false) {
                $studentDetails['dob'] = 1;
            } else {
                $studentDetails['dob'] = Carbon::createFromFormat('d-m-Y', $row[3])->format('Y-m-d');
            }
        } else {
            $studentDetails['dob'] = null;
        }
        $studentDetails['phone'] = trim($row[5]) ?? null;
        $studentDetails['guardian_name'] = trim($row[11]) ?? null;
        $studentDetails['guardian_phone'] = trim($row[12]) ?? null;
        $studentDetails['last_month_salary'] = trim($row[10]) ?? null;
        $tradeDetails = Trade::where('name', trim($row[6]))->where('type', $this->centreType)->first();
        $studentDetails['trade_course'] = $tradeDetails->id ?? null;
        $studentDetails['updated_email'] = trim($row[56]) ?? null;
        $studentDetails['updated_mobile'] = trim($row[57]) ?? null;
        if (trim($row[15]) != "") {
            $studentDetailsBatchId = Batch::where('name', trim($row[15]))->where('centre_id', $this->centreId)->first();
            $studentDetails['batch_id'] = $studentDetailsBatchId->id ?? null;
        } else {
            $studentDetails['batch_id'] = null;
        }
        if (in_array($row[16], ['no', 'n', '0', "", 'No'])) {
            $studentDetails['contactability'] = 0;
        } else {
            $studentDetails['contactability'] = 1;
        }
        $studentDetails['not_contactable_reason'] = trim($row[17]) ?? null;
        $studentDetails['interview1_company_name'] = trim($row[18]) ?? null;
        if (trim($row[19]) != "") {
            if (Carbon::hasFormat($row[19], 'd-m-Y') == false) {
                $studentDetails['interview1_date'] = 1;
            } else {
                $studentDetails['interview1_date'] = Carbon::createFromFormat('d-m-Y', $row[19])->format('Y-m-d');
            }
        } else {
            $studentDetails['interview1_date'] = null;
        }
        $interview1Result = array_search(trim($row[20]), config('staticcontent.interview_result'));
        if ($interview1Result !== false) {
            $studentDetails['interview1_result'] = $interview1Result;
        } else {
            $studentDetails['interview1_result'] = null;
        }
        $studentDetails['interview2_company_name'] = trim($row[21]) ?? null;
        if (trim($row[22]) != "") {
            if (Carbon::hasFormat($row[22], 'd-m-Y') == false) {
                $studentDetails['interview2_date'] = 1;
            } else {
                $studentDetails['interview2_date'] = Carbon::createFromFormat('d-m-Y', $row[22])->format('Y-m-d');
            }
        } else {
            $studentDetails['interview2_date'] = null;
        }
        $interview2Result = array_search(trim($row[23]), config('staticcontent.interview_result'));
        if ($interview2Result !== false) {
            $studentDetails['interview2_result'] = $interview2Result;
        } else {
            $studentDetails['interview2_result'] = null;
        }
        $studentDetails['interview3_company_name'] = trim($row[24]) ?? null;
        if (trim($row[25]) != "") {
            if (Carbon::hasFormat($row[25], 'd-m-Y') == false) {
                $studentDetails['interview3_date'] = 1;
            } else {
                $studentDetails['interview3_date'] = Carbon::createFromFormat('d-m-Y', $row[25])->format('Y-m-d');
            }
        } else {
            $studentDetails['interview3_date'] = null;
        }
        $interview3Result = array_search(trim($row[26]), config('staticcontent.interview_result'));
        if ($interview3Result !== false) {
            $studentDetails['interview3_result'] = $interview3Result;
        } else {
            $studentDetails['interview3_result'] = null;
        }
        if (in_array($row[27], ['no', 'n', '0', "", 'No'])) {
            $studentDetails['placed'] = 0;
        } else {
            $studentDetails['placed'] = 1;
        }
        $monthOfJoining = array_search(trim($row[28]), config('staticcontent.month_of_joining'));
        if ($monthOfJoining !== false) {
            $studentDetails['month_of_joining'] = $monthOfJoining;
        } else {
            $studentDetails['month_of_joining'] = null;
        }
        if (trim($row[29]) != "") {
            if (Carbon::hasFormat($row[29], 'd-m-Y') == false) {
                $studentDetails['date_of_updation'] = 1;
            } else {
                $studentDetails['date_of_updation'] = Carbon::createFromFormat('d-m-Y', $row[29])->format('Y-m-d');
            }
        } else {
            $studentDetails['date_of_updation'] = null;
        }
        $studentDetails['remarks'] = trim($row[30]) ?? null;
        return $studentDetails;
    }

    private function getPlacementData($placementkey, $placementVal, $row)
    {
        $placementTypeDetails = PlacementType::where('type', $placementkey)->first();
        $placementData['placement_type'] = $placementTypeDetails->id;
        $placementData['placement_type_name'] = $placementTypeDetails->name;
        if (!empty($row[$placementVal + 1])) {
            $placementStatusDetails = PlacementStatus::where('name', $row[$placementVal + 1])->first();
            $placementData['pl_status_type'] = $placementStatusDetails->type ?? null;
            $placementData['placement_status'] = $placementStatusDetails->id ?? $row[$placementVal + 1];
        } else {
            $placementData['pl_status_type'] = '';
            $placementData['placement_status'] = '';
        }
        if (
            $placementData['pl_status_type'] == PlacementStatus::EMPLOYED_STATUS
            || $placementData['pl_status_type'] == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS
        ) {
            //Employed (Job/Apprenticeship/Internship)
            $placementData = $this->getJobApprenticeData($row, $placementVal, $placementData);
        } elseif ($placementData['pl_status_type'] == PlacementStatus::SELF_EMPLOYED_STATUS) { //Self employed
            $placementData = $this->getSelfemployedData($row, $placementVal, $placementData);
        } elseif ($placementData['pl_status_type'] == PlacementStatus::HIGHER_STUDIES_STATUS) { //Higher studies
            $placementData = $this->getHigherstudiesData($row, $placementVal, $placementData);
        } elseif (
            $placementData['pl_status_type'] == PlacementStatus::NOT_WORKING_STATUS
            || $placementData['pl_status_type'] == PlacementStatus::LOOKING_FOR_WORK_STATUS
            || $placementData['pl_status_type'] == PlacementStatus::DROPOUT_STATUS
        ) {
            //Not working or Looking for work/Drop-out. Here this field is reason for not working
            $placementData['placement_drop_out_reason'] = isset($row[$placementVal + 13])
                ? $row[$placementVal + 13] : '';
        }
        return $placementData;
    }

    private function validateFields($id, $studentDetails)
    {    
        $errors = [];
        if ($id) {
            $errors = $this->userExistValidation($id, $studentDetails);
        } else {
            $errors = $this->userNotExistValidation($studentDetails);
        }
        if (empty($studentDetails['student_name'])) {
            $errors[] = trans('admin.student_missing');
        }
        if (ctype_alpha(str_replace(' ', '', $studentDetails['student_name'])) === false) {
            $errors[] = trans('admin.invalid_student_name');
        }
        if (empty($studentDetails['gender'])) {
            $errors[] = trans('admin.student_gender_missing');
        }
        if (empty($studentDetails['email']) && empty($studentDetails['phone'])) {
            $errors[] = trans('admin.either_email_mobile_required');
        }
        if (!empty($studentDetails['email'])) {
            if (!preg_match('/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/', $studentDetails['email'])) {
                $errors[] = trans('admin.invalid_email');
            }
        }
        if (!empty($studentDetails['phone'])) {
            if (!preg_match('/^[0-9]{10}+$/', $studentDetails['phone'])) {
                $errors[] = trans('admin.invalid_mobile_number');
            }
        }
        if (!empty($studentDetails['guardian_phone'])) {
            if (!preg_match('/^[0-9]{10}+$/', $studentDetails['guardian_phone'])) {
                $errors[] = trans('admin.invalid_guardian_mobile_number');
            }
        }
        // if (empty($studentDetails['trade_course'])) {
        //     $errors[] = trans('admin.invalid_trade');
        // }
        // if (empty($studentDetails['batch_id'])) {
        //     $errors[] = trans('admin.batch_id_missing');
        // }
        if (!empty($studentDetails['trade_course'])) {
            $centerType = $this->centreType;
            $tradeDetails = Trade::where('type', $centerType)->where('id', $studentDetails['trade_course'])->first();
            if (empty($tradeDetails->id)) {
                $errors[] = trans('admin.trade_mismatch');
            }
        }
        if ($studentDetails['dob'] == 1) {
            $errors[] = trans('admin.dob_format_invalid');
        }

        // Intentionally disabled following validations 
        // do not remove
        // if ($studentDetails['contactability'] == 0) {
        //     if ($studentDetails['not_contactable_reason'] == null) {
        //         $errors[] = trans('admin.reason_invalid');
        //     }
        // }
        // if ($studentDetails['interview1_company_name'] == null) {
        //     $errors[] = trans('admin.interview1_company_invalid');
        // }
        // if ($studentDetails['interview1_date'] == null || $studentDetails['interview1_date'] == 1) {
        //     $errors[] = trans('admin.interview1_date_invalid');
        // }
        if($studentDetails['interview1_date'] == 1) {
            $errors[] = trans('admin.interview1_date_invalid');
        }
        // if ($studentDetails['interview1_result'] == null) {
        //     $errors[] = trans('admin.interview1_result_invalid');
        // }
        if ($studentDetails['interview2_date'] == 1) {
            $errors[] = trans('admin.interview2_date_invalid');
        }
        if ($studentDetails['interview3_date'] == 1) {
            $errors[] = trans('admin.interview3_date_invalid');
        }
        if ($studentDetails['date_of_updation'] == 1) {
            $errors[] = trans('admin.date_of_updation_invalid');
        }

        // Intentional validation comments ends here
        return $errors;
    }

    private function validatePlacement($placementData)
    {
        $rules = [
            'placement_type' => 'nullable|exists:placement_types,id',
            'placement_user_id' => 'required|exists:users,id',
            'placement_status' => 'nullable|exists:placement_status,id',
            'placement_company' => 'nullable|max:100',
            'placement_designation' => 'nullable|max:100',
            'placement_location' => 'nullable|exists:locations,id',
            'placement_sector' => 'nullable|exists:sectors,id',
            'placement_offerletter_status' => 'nullable|exists:offerletter_status,id',
            'placement_offerletter_type' => 'nullable|exists:offerletter_types,id',
            'placement_course' => 'nullable|exists:placement_courses,id',
        ];
        return Validator::make($placementData, $rules);
    }

    private function setUser($user, $userDetails, $userId, $password)
    {
        $user->name = $userDetails['student_name'];
        $user->email = $userDetails['email'];
        $user->mobile = $userDetails['phone'];
        $user->gender = strtolower($userDetails['gender']);
        $user->centre_id = $this->centreId;
        $user->organisation_id = Centre::where('id', $this->centreId)->first()->organisation_id;
        $user->type = User::TYPE_STUDENT;
        if (empty($userId)) {
            $user->password = Hash::make($password);
        }
        $user->created_platform = User::CREATED_PLATFORM_ADMIN;
        $user->tenant_id = getTenant();
        return $user;
    }
    private function setStudent($student, $studentDetails, $userId)
    {
        $student->user_id = $userId;
        $student->date_of_birth = $studentDetails['dob'];
        $student->marital_status = $studentDetails['marital_status'];
        $student->experience = $studentDetails['work_experience'];
        $student->educational_qualification_id = $studentDetails['educational_qualification_id'];
        $student->guardian_name = $studentDetails['guardian_name'];
        $student->guardian_mobile = $studentDetails['guardian_phone'];
        $student->guardian_income = $studentDetails['guardian_income'];
        $student->last_month_salary = $studentDetails['last_month_salary'];
        $student->trade_id = $studentDetails['trade_course'];
        $student->batch_id = $studentDetails['batch_id'];
        $student->updated_email = $studentDetails['updated_email'];
        $student->updated_mobile = $studentDetails['updated_mobile'];
        $student->contactability = $studentDetails['contactability'];
        $student->not_contactable_reason = $studentDetails['not_contactable_reason'];
        $student->interview1_company_name = $studentDetails['interview1_company_name'];
        $student->interview1_date = $studentDetails['interview1_date'];
        $student->interview1_result = $studentDetails['interview1_result'];
        $student->interview2_company_name = $studentDetails['interview2_company_name'];
        $student->interview2_date = $studentDetails['interview2_date'];
        $student->interview2_result = $studentDetails['interview2_result'];
        $student->interview3_company_name = $studentDetails['interview3_company_name'];
        $student->interview3_date = $studentDetails['interview3_date'];
        $student->interview3_result = $studentDetails['interview3_result'];
        $student->placed = $studentDetails['placed'];
        $student->month_of_joining = $studentDetails['month_of_joining'];
        $student->date_of_updation = $studentDetails['date_of_updation'];
        $student->guardian_occupation = $studentDetails['guardian_occupation'];
        $student->remarks = $studentDetails['remarks'];
        return $student;
    }

    private function addPlacement($placementData)
    {
        $placement = new Placement();
        $placementDetail = Placement::where('user_id', $placementData['placement_user_id'])
            ->where("placement_type_id", $placementData['placement_type'])
            ->first();

        if (!$placementDetail) {
            $placement = $this->setPlacement($placementData, $placement);
            $placement->save();
        } else {
            $placement = $this->setPlacement($placementData, $placementDetail);
            $placement->update();
        }
        DB::commit();
    }

    private function deleteEmptyStatusPlacement($placementData)
    {
        $placementDetail = Placement::where('user_id', $placementData['placement_user_id'])
            ->where("placement_type_id", $placementData['placement_type'])
            ->first();
        if ($placementDetail) {
            $placementDetail->delete();
        }
    }

    private function setPlacement($request, $placement)
    {
        $placement->tenant_id = getTenant();
        $placement->user_id = $request['placement_user_id'];
        $placement->placement_type_id = $request['placement_type'];
        $placement->placement_status_id = $request['placement_status'];
        $placement->offerletter_type_id =
            isset($request['placement_offerletter_type']) ? (($request['placement_offerletter_type']) ?: null)
            : null;
        $placement->offerletter_status_id =
            isset($request['placement_offerletter_status']) ? (($request['placement_offerletter_status']) ?: null)
            : null;
        $placement->placement_course_id = isset($request['placement_course']) ?
            (($request['placement_course']) ?: null) : null;
        $placement->company = isset($request['placement_company']) ? (($request['placement_company'])) : null;
        $placement->designation = isset($request['placement_designation']) ?
            (($request['placement_designation']) ?: null) : null;
        $placement->sector_id = isset($request['placement_sector']) ?
            (($request['placement_sector']) ?: null) : null;
        $placement->location_id = isset($request['placement_location']) ?
            (($request['placement_location']) ?: null) : null;
        $placement->salary = isset($request['placement_salary']) ?
            (($request['placement_salary']) ?: null) : null;
        $placement->reason = isset($request['placement_drop_out_reason']) ?
            (($request['placement_drop_out_reason'])) : null;
        return $placement;
    }

    private function exportData($finalResultAll, $finalResultErrors, $errorCount, $placementSuccess)
    {
        $export = collect($finalResultAll);
        $errorExport = collect($finalResultErrors);
        $uniqid = Str::random();
        $errorFileName = 'student_centre_downlods/error/' . 'error_' . $uniqid . '.csv';
        $fileName = 'student_centre_downlods/' . $uniqid . '.csv';
        Excel::store(new StudentCentreSuccessErrorExport($export), $fileName, 's3');
        Excel::store(new StudentCentreSuccessErrorExport($errorExport), $errorFileName, 's3');
        $data['status'] = 1;
        $data['message'] = trans('admin.file_imported');
        if ($errorCount == 0 && $placementSuccess == 1) {
            $data['error_status'] = 0;
        } else {
            $data['error_status'] = 1;
            $data['error_file_name'] = generateTempUrl($errorFileName);
        }
        $data['uploaded_file_name'] = generateTempUrl($fileName);
        return $data;
    }

    private function getModelValue($modelName, $fetchValue)
    {
        $model = 'App\Models\\' . $modelName;
        $fetchValue = trim($fetchValue, chr(0xC2) . chr(0xA0));
        return $model::where('name', $fetchValue)->first();
    }

    private function placementInformation($userId, $finalRes, $placementSuccess, $row, $statusColumn)
    {
        $placementInformation = [];
        foreach ([30, 43, 58] as $placementkey => $placementVal) {
            //14,27,42m are starting of each placement data
            $placementData = [];
            $placementData = $this->getPlacementData($placementkey, $placementVal, $row);
            $placementData['placement_user_id'] = $userId;
            $placementErrors = $this->validatePlacement($placementData);
            if (!empty($placementData['placement_status'])) {
                if ($placementErrors->fails()) {
                    $placementSuccess = 0;
                    $finalRes[$statusColumn] = $finalRes[$statusColumn] .
                        ',' . $placementData['placement_type_name'] . " failed " .
                        ':' . implode(',', $placementErrors->errors()->all());
                } else {
                    $this->addPlacement($placementData);
                    $finalRes[$statusColumn] = $finalRes[$statusColumn] . ','
                        . $placementData['placement_type_name'] . ' ' . "placement success";
                }
            } else {
                $this->deleteEmptyStatusPlacement($placementData);
            }
        }
        $placementInformation['finalRes'] = $finalRes;
        $placementInformation['placementSuccess'] = $placementSuccess;
        return $placementInformation;
    }
    private function studentAddOrUpdate($user, $studentDetails, $id, $statusColumn, $finalRes)
    {
        $password = User::LEARNER_PASSWORD;
        $studentInformation = [];
        $user = $this->setUser($user, $studentDetails, $id, $password);
        $validEmail = true;
        if ($id) {
            $user->update();
            if (!empty($user->studentDetail)) {
                $student = $this->setStudent($user->studentDetail, $studentDetails, $id);
                $student->update();
            }
        } else {
            if (auth()->user()->hasPermissionTo('activity.needs.approval')) {
                $approvalReferenceId = Approval::where('reference_id', $this->approvalConst)->first();
                if (empty($approvalReferenceId)) {
                    $approvalReferenceId = $this->setApproval();
                }
                $user->is_approved = User::TYPE_NOT_APPROVED;
                $user->approval_group = $approvalReferenceId->reference_id;
            }
            $user->status = User::INACTIVE_STATUS;
            $user->reg_status = User::INACTIVE_STATUS;
            $user->save();
            $setUserPhase = setUserPhase($user);

            $user->syncRoles("student");
            $student = new StudentDetail();
            $student = $this->setStudent($student, $studentDetails, $user->id);
            $student->save();
            if (!empty($user->email)) {
                $object = (object)['name' => $user->name, 'email' => $user->email, 'password' => $password];
                $post['email'] =  $user->email;
                $jwt = JWT::encode($post, env('SERVICE_JWT_SECRET'));
                $url = env('COMMON_SERVICE_URL') . "/api/v1/email-verifier";
                $response = Http::withHeaders([
                    'X-centre-token' => $jwt,
                    'Accept' => 'application/json'
                ])->post($url, $post);
                if (json_decode($response->body())->status == User::CHECK_EMAIL_IS_VALID) {
                    $this->sendEmail($user->email, $user->name);
                } else {
                    $validEmail = false;
                }
            }
        }
        DB::commit();
        if ($validEmail) {
            $finalRes[$statusColumn] = "Student details success";
        } else {
            $finalRes[$statusColumn] = "Student details success. Email not sent.";
        }
        $studentInformation['finalRes'] = $finalRes;
        $studentInformation['userId'] = $user->id;
        return $studentInformation;
    }

    /**
     * Set Approval Data
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [collection]
     */
    private function setApproval()
    {
        $approval = new Approval();
        $approval->type = Approval::TYPE_BULK;
        $approval->title = trans('admin.learner_bulk_approval_created');
        $approval->model = Approval::TYPE_LEARNER_MODEL;
        $approval->status = Approval::TYPE_NEEDS_APPROVAL;
        $approval->reference_id = $this->approvalConst;
        $approval->save();
        return $approval;
    }

    private function getJobApprenticeData($row, $placementVal, $placementData)
    {
        $placementData['placement_company'] = isset($row[$placementVal + 2]) ? $row[$placementVal + 2] : '';
        $placementData['placement_designation'] = isset($row[$placementVal + 3]) ? $row[$placementVal + 3] : '';
        $locationDetails = $this->getModelValue('Location', trim($row[$placementVal + 4]));
        $placementData['placement_location'] = $locationDetails->id ?? trim($row[$placementVal + 4]);
        $sectorDetails = $this->getModelValue('Sector', trim($row[$placementVal + 5]));
        $placementData['placement_sector'] = $sectorDetails->id ?? trim($row[$placementVal + 5]);
        $offerlettreStatusDetails = $this->getModelValue('OfferletterStatus', trim($row[$placementVal + 6]));
        $placementData['placement_offerletter_status'] = $offerlettreStatusDetails->id ??
            trim($row[$placementVal + 6]);
        $offerletterTypeDetails = $this->getModelValue('OfferletterType', trim($row[$placementVal + 7]));
        $placementData['placement_offerletter_type'] = $offerletterTypeDetails->id ?? trim($row[$placementVal + 7]);
        $placementData['placement_salary'] = (isset($row[$placementVal + 8])
            && is_numeric($row[$placementVal + 8])) ? $row[$placementVal + 8] : null;
        return $placementData;
    }
    private function getHigherstudiesData($row, $placementVal, $placementData)
    {
        $courseDetails = $this->getModelValue('PlacementCourse', trim($row[$placementVal + 11]));
        $placementData['placement_course'] = $courseDetails->id ?? trim($row[$placementVal + 11]);
        $locationDetails = $this->getModelValue('Location', trim($row[$placementVal + 12]));
        $placementData['placement_location'] = $locationDetails->id ??
            trim($row[$placementVal + 12]);
        return $placementData;
    }

    private function getSelfemployedData($row, $placementVal, $placementData)
    {
        $sectorDetails = $this->getModelValue('Sector', trim($row[$placementVal + 9]));
        $placementData['placement_sector'] = $sectorDetails->id ?? trim($row[$placementVal + 9]);
        $placementData['placement_salary'] = (isset($row[$placementVal + 10])
            && is_numeric($row[$placementVal + 10])) ? $row[$placementVal + 10] : null;
        return $placementData;
    }

    private function userExistValidation($id, $studentDetails)
    {
        $errors = [];
        $emailCount = 0;
        $phoneCount = 0;
        $user = User::where('id', $id)->first();
        if (!$user) {
            $errors[] = trans('admin.invalid_id');
        }
        if (!empty($studentDetails['email'])) {
            $emailCount = User::where('email', $studentDetails['email'])
                ->whereNotIn('type', [User::TYPE_ADMIN])
                ->WhereNull('deleted_at')->where('id', '!=', $id)->get()->count();
        }
        if (!empty($studentDetails['phone'])) {
            $phoneCount = User::where('mobile', $studentDetails['phone'])
                ->whereNotIn('type', [User::TYPE_ADMIN])
                ->WhereNull('deleted_at')->where('id', '!=', $id)->get()->count();
        }
        if ($emailCount > 0) {
            $errors[] = trans('admin.student_email_exist');
        }
        if ($phoneCount > 0) {
            $errors[] = trans('admin.student_phone_exist');
        }
        if (!auth()->user()->can('learner.update')) {
            $errors[] = trans('admin.learner_no_update_permission');
        }
        return $errors;
    }
    private function userNotExistValidation($studentDetails)
    {
        $errors = [];
        $emailCount = 0;
        $phoneCount = 0;
        if (!empty($studentDetails['email'])) {
            $emailCount = User::where('email', $studentDetails['email'])
                ->whereNotIn('type', [User::TYPE_ADMIN])
                ->WhereNull('deleted_at')->get()->count();
        }
        if (!empty($studentDetails['phone'])) {
            $phoneCount = User::where('mobile', $studentDetails['phone'])
                ->whereNotIn('type', [User::TYPE_ADMIN])
                ->WhereNull('deleted_at')->get()->count();
        }
        $workingMode = $this->workingMode;
        if ($workingMode == 0) {
            $errors[] = trans('admin.learnpi_workingmode_centreadmin');
        }
        if ($emailCount > 0) {
            $errors[] = trans('admin.student_email_exist');
        }
        if ($phoneCount > 0) {
            $errors[] = trans('admin.student_phone_exist');
        }
        if (!auth()->user()->can('learner.create')) {
            $errors[] = trans('admin.learner_no_create_permission');
        }
        return $errors;
    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 3;
    }

    /**
     * Send registration message through email
     *
     * @param mixed $user
     * @param mixed $message
     */
    private function sendEmail($userEmail, $userName)
    {
        $password = User::LEARNER_PASSWORD;
        $domainEmail = env('MSG91_EMAIL');
        $templateName = "student-reg-for-quest-app";
        $domain = env('MSG91_DOMAIN');
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.msg91.com/api/v5/email/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n  \"to\": [\n    {\n       \"email\": \"$userEmail\"\n    }\n  ],\n  \"from\": {\n    \"name\": \"Quest\",\n    \"email\": \"$domainEmail\"\n  },\n \n  \"domain\": \"$domain\",\n  \"mail_type_id\": \"1 \",\n   \n  \"template_id\": \"$templateName\",\n  \"variables\":  {\n    \"VAR1\": \"$userName\",\n    \"VAR2\": \"$userEmail\",\n    \"VAR3\": \"$password\"\n  }\n}",
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Content-Type: application/json",
                "authkey:" . env('Msg91_KEY') . ""
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
    }
}
