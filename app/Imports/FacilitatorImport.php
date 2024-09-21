<?php

namespace App\Imports;

use App\Exports\FacilitatorSuccessErrorExport;
use App\Models\FacilitatorDetail;
use App\Models\User;
use App\Models\Approval;
use App\Models\EmailVerify;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use App\Mail\FacilitatorAdded;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

/**
 * Class FacilitatorDataImport
 * @package App\Imports
 */
class FacilitatorImport implements ToCollection, WithHeadingRow
{
    public $data;
    protected $approvalConst;
    protected $userId;

    public function __construct($approvalConst, $userId)
    {
        $this->approvalConst = $approvalConst;
        $this->userId = $userId;
    }
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {

        $organisationNameArray = $collection->where('organisation_name', '!=', null)
            ->unique('organisation_name')->pluck('organisation_name')->toArray();
        $organisationNameArray = array_map(function ($ee) {
            return trim($ee);
        }, $organisationNameArray);
        $organisationNameArray = collect($organisationNameArray);
        $organisations = getOrganisationFromName($organisationNameArray); //Collection with ID and Name
        $centres = getCentreFromOrgName($organisationNameArray);
        $finalResultAll = [];
        $finalResultErrors = [];

        $keyArray = array(
            0 => 'id',
            1 => 'facilitator_name',
            2 => 'organisation_name',
            3 => 'centre_name',
            4 => 'gender',
            5 => 'designation',
            6 => 'email',
            7 => 'dob',
            8 => 'phone',
            9 => 'highest_qualification',
            10 => 'work_experience',
            11 => 'is_super_facilitator',
            12 => 'is_master_trainer',
        );
        $errorCount = 0;
        foreach ($collection->chunk(10) as $chunk) {
            foreach ($chunk as $row) {
                $finalRes = [];
                $errors = [];

                $this->checkExcelFormat($row, $keyArray);
                $facilitatorDetails = $this->fetchdata($row, $organisations, $centres);
                foreach ($row as $value) {
                    $finalRes[] = $value;
                }
                if ($row['id']) {
                    $user = User::where('id', (string)$row['id'])->first();
                    $errors = $this->validateFields($row['id'], $facilitatorDetails);
                } else {
                    $user = new User();
                    $errors = $this->validateFields("", $facilitatorDetails);
                }

                if (empty($errors)) {
                    $password = User::FACILITATOR_PASSWORD;
                    $validEmail = true;
                    $user = $this->setUser($user, $facilitatorDetails, $row['id'], $password);
                    if ($row['id']) {
                        if ($facilitatorDetails['is_master_trainer'] == User::NOT_MASTER_TRAINER) {
                            $user->masterTrainerUsers()->detach();
                        }
                        if ($facilitatorDetails['is_super_facilitator'] == User::SUPER_FACILITATOR) {
                            if ($user->organisation_id != $facilitatorDetails['organisation_id']) {
                                $user->centres()->detach();
                            }
                        }
                        $user->update();
                        if (!empty($user->facilitatorDetail)) {
                            $facilitator = $this
                                ->setFacilitator($user->facilitatorDetail, $facilitatorDetails, $row['id']);
                            $facilitator->update();
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
                        $user->created_by = $this->userId;
                        $user->save();
                        setUserPhase($user);
                        $user->syncRoles("facilitator");
                        $facilitator = new FacilitatorDetail();
                        $facilitator = $this->setFacilitator($facilitator, $facilitatorDetails, $user->id);
                        $facilitator->save();
                        if (!empty($facilitatorDetails['email'])) {
                            $this->setEmailVerify($facilitatorDetails, $user->id);

                            $object = (object)['name' => $user->name, 'email' => $user->email, 'password' => $password];
                            $post['email'] =  $user->email;
                            $jwt = JWT::encode($post, env('SERVICE_JWT_SECRET'));
                            $url = env('COMMON_SERVICE_URL') . "/api/v1/email-verifier";

                            $response = Http::withHeaders([
                                'X-centre-token' => $jwt,
                                'Accept' => 'application/json'
                            ])->post($url, $post);
                            if (json_decode($response->body())->status == User::CHECK_EMAIL_IS_VALID) {
                                $this->sendEmail($user->email,$user->name);
                            } else {
                                $validEmail = false;
                            }
                        }
                        DB::commit();
                        if ($validEmail) {
                            $finalRes[] = "Success";
                        } else {
                            $finalRes[] = "Success. Email not sent.";
                        }
                        $finalResultAll[] = $finalRes;
                    }
                } else {
                    $errorCount++;
                    $finalRes[] = "Failed " . implode(',', $errors);
                    $finalResultAll[] = $finalRes;
                    $finalResultErrors[] = $finalRes;
                }
            }
        }
        $export = collect($finalResultAll);
        $errorExport = collect($finalResultErrors);

        $uniqid = Str::random();
        $errorFileName = 'facilitator_downlods/error/' . 'error_' . $uniqid . '.csv';
        $fileName = 'facilitator_downlods/' . $uniqid . '.csv';
        Excel::store(new FacilitatorSuccessErrorExport($export), $fileName, 's3');
        Excel::store(new FacilitatorSuccessErrorExport($errorExport), $errorFileName, 's3');
        $data['status'] = 1;
        $data['message'] = trans('admin.file_imported');
        if ($errorCount == 0) {
            $data['error_status'] = 0;
        } else {
            $data['error_status'] = 1;
            $data['error_file_name'] = generateTempUrl($errorFileName);
        }
        $data['uploaded_file_name'] = generateTempUrl($fileName);
        $this->data = $data;
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
        $approval->title = trans('admin.facilitator_bulk_approval_created');
        $approval->model = Approval::TYPE_FACILITATOR_MODEL;
        $approval->status = Approval::TYPE_NEEDS_APPROVAL;
        $approval->reference_id = $this->approvalConst;
        $approval->save();
        return $approval;
    }

    private function checkExcelFormat($row, $keyArray)
    {
        $i = 0;
        foreach ($row as $key => $value) {
            if ($key != $keyArray[$i]) {
                throw ValidationException::withMessages(
                    array("file" =>
                    "Invalid Excel Format," . ucfirst(str_replace('_', ' ', $keyArray[$i])) . " Missing")
                );
            }
            $i++;
        }
    }

    private function fetchdata($row, $organisations, $centres)
    {
        $facilitatorDetails['facilitator_name'] = trim($row['facilitator_name']);
        $facilitatorDetails['email'] = trim($row['email']) ?? null;
        $facilitatorDetails['phone'] = trim($row['phone']) ?? null;
        $facilitatorDetails['centre_name'] = $row['centre_name'] ?? null;
        $facilitatorDetails['organisation_name'] = $row['organisation_name'] ?? null;
        $facilitatorDetails['gender'] = config('staticcontent.gender.' .
            trim(ucwords($row['gender']))) ?? null;
        $orgDetails = $this->getCollectionCaseInsensitiveString($organisations, 'name', trim($row['organisation_name']))
            ->first();
        $facilitatorDetails['organisation_id'] = $orgDetails->id ?? null;
        $centreDetails = $this->getCollectionCaseInsensitiveString($centres, 'name', trim($row['centre_name']))
            ->first();
        $facilitatorDetails['centre_id'] = $centreDetails->id ?? null;
        $facilitatorDetails['highest_qualification'] = $row['highest_qualification'] ?? null;
        $facilitatorDetails['designation'] = $row['designation'] ?? null;
        $facilitatorDetails['work_experience'] = $row['work_experience'] ?? null;
        if (trim($row['dob']) != "") {
            if (Carbon::hasFormat($row['dob'], 'd-m-Y') == false) {
                $facilitatorDetails['dob'] = 1;
            } else {
                $facilitatorDetails['dob'] = Carbon::createFromFormat('d-m-Y', $row['dob'])->format('Y-m-d');
            }
        } else {
            $facilitatorDetails['dob'] = null;
        }
        $row['is_super_facilitator'] = strtolower($row['is_super_facilitator']);
        $row['is_master_trainer'] = strtolower($row['is_master_trainer']);
        if (in_array($row['is_super_facilitator'], ['no', 'n', '0', ''])) {
            $facilitatorDetails['is_super_facilitator'] = User::NOT_SUPER_FACILITATOR;
        } else {
            $facilitatorDetails['is_super_facilitator'] = User::SUPER_FACILITATOR;
        }
        if (in_array($row['is_master_trainer'], ['no', 'n', '0', ''])) {
            $facilitatorDetails['is_master_trainer'] = User::NOT_MASTER_TRAINER;
        } else {
            $facilitatorDetails['is_master_trainer'] = User::MASTER_TRAINER;
        }
        return $facilitatorDetails;
    }

    private function getCollectionCaseInsensitiveString($collection, $attribute, $value)
    {
        $value = strtolower($value);
        $collection = $collection->filter(function ($item) use ($attribute, $value) {
            return strtolower($item[$attribute]) == strtolower($value);
        });
        return $collection;
    }

    private function validateFields($id, $facilitatorDetails)
    {
        $errors = [];
        $emailCount = 0;
        $phoneCount = 0;
        if ($id) {
            $user = User::where('id', (string)$id)->first();
            if (!$user) {
                $errors[] = trans('admin.invalid_id');
                return $errors;
            }
            $authUser = auth()->user();
            $user['centre_id'] = $facilitatorDetails['centre_id'];
            $user['organisation_id'] = $facilitatorDetails['organisation_id'];
            if (!policy($user)->update($authUser, $user)) {
                $errors[] = trans('admin.user_permission');
            }
            if (!empty($facilitatorDetails['email'])) {
                $emailCount = User::where('email', $facilitatorDetails['email'])
                    ->whereNotIn('type', [User::TYPE_ADMIN])
                    ->WhereNull('deleted_at')->where('id', '!=', $id)->get()->count();
            }
            if (!empty($facilitatorDetails['phone'])) {
                $phoneCount = User::where('mobile', $facilitatorDetails['phone'])
                    ->whereNotIn('type', [User::TYPE_ADMIN])
                    ->WhereNull('deleted_at')->where('id', '!=', $id)->get()->count();
            }
            if (!auth()->user()->can('facilitator.update')) {
                $errors[] = trans('admin.facilitator_no_update_permission');
            }
        } else {
            if (!empty($facilitatorDetails['email'])) {
                $emailCount = User::where('email', $facilitatorDetails['email'])
                    ->whereNotIn('type', [User::TYPE_ADMIN])
                    ->WhereNull('deleted_at')->get()->count();
            }
            if (!empty($facilitatorDetails['phone'])) {
                $phoneCount = User::where('mobile', $facilitatorDetails['phone'])
                    ->whereNotIn('type', [User::TYPE_ADMIN])
                    ->WhereNull('deleted_at')->get()->count();
            }
            if (!auth()->user()->can('facilitator.create')) {
                $errors[] = trans('admin.facilitator_no_create_permission');
            }
        }
        if (empty($facilitatorDetails['facilitator_name'])) {
            $errors[] = trans('admin.facilitator_missing');
        }
        if (empty($facilitatorDetails['email']) && empty($facilitatorDetails['phone'])) {
            $errors[] = trans('admin.either_email_mobile_required');
        }
        if (!empty($facilitatorDetails['email'])) {
            if (!preg_match('/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/', $facilitatorDetails['email'])) {
                $errors[] = trans('admin.invalid_email');
            }
        }
        if (!empty($facilitatorDetails['phone'])) {
            if (!preg_match('/^[0-9]{10}+$/', $facilitatorDetails['phone'])) {
                $errors[] = trans('admin.invalid_mobile_number');
            }
        }
        if ($emailCount > 0) {
            $errors[] = trans('admin.facilitator_email_exist');
        }
        if ($phoneCount > 0) {
            $errors[] = trans('admin.facilitator_phone_exist');
        }
        if (empty($facilitatorDetails['organisation_id'])) {
            $errors[] = trans('admin.organisation_name_missing');
        }
        if (empty($facilitatorDetails['centre_id'])) {
            $errors[] = trans('admin.centre_name_missing');
        }
        if (empty($facilitatorDetails['gender'])) {
            $errors[] = trans('admin.facilitator_gender_missing');
        }
        if ($facilitatorDetails['dob'] == 1) {
            $errors[] = trans('admin.dob_format_invalid');
        }
        return $errors;
    }

    private function setUser($user, $userDetails, $userId, $password)
    {
        $authUser = auth()->user();
        $user->name = $userDetails['facilitator_name'];
        $user->email = $userDetails['email'];
        $user->mobile = $userDetails['phone'];
        $user->gender = strtolower($userDetails['gender']);
        $user->organisation_id = $userDetails['organisation_id'];
        $user->centre_id = $userDetails['centre_id'];
        $user->status = User::ACTIVE_STATUS;
        $user->type = User::TYPE_FACILITATOR;
        if (empty($userId)) {
            $user->password = Hash::make($password);
        }
        $user->created_platform = User::CREATED_PLATFORM_ADMIN;
        $user->is_master_trainer = $userDetails['is_master_trainer'];
        $user->is_super_facilitator = $userDetails['is_super_facilitator'];
        $user->tenant_id = getTenant();
        $user->created_by = $authUser->id;
        return $user;
    }
    private function setFacilitator($facilitator, $facilitatorDetails, $userId)
    {
        $facilitator->user_id = $userId;
        $facilitator->designation = $facilitatorDetails['designation'];
        $facilitator->qualification = $facilitatorDetails['highest_qualification'];
        $facilitator->experience = $facilitatorDetails['work_experience'];
        $facilitator->date_of_birth = $facilitatorDetails['dob'];
        $facilitator->user_approved = 1;
        return $facilitator;
    }
    private function setEmailVerify($facilitatorDetails, $userId)
    {
        $emailVerify = new EmailVerify();
        $emailVerify->user_id = $userId;
        $emailVerify->email =  $facilitatorDetails['email'];
        $emailVerify->save();
        return $emailVerify;
    }
    /**
     * Send registration message through email
     *
     * @param mixed $user
     * @param mixed $message
     */
    private function sendEmail($userEmail,$userName)
    {
        $password = User::FACILITATOR_PASSWORD;
        $domainEmail = env('MSG91_EMAIL');
        $templateName = "facilitator-reg-for-quest-app";
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
