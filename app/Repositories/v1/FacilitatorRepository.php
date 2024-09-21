<?php

namespace App\Repositories\v1;

use App\Exports\FacilitatorsExport;
use App\Models\FacilitatorDetail;
use App\Models\User;
use App\Models\Approval;
use App\Models\MasterTrainerUser;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\QueryBuilder;
use App\Imports\FacilitatorImport;
use App\Models\EmailVerify;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use App\Services\Filter\FacilitatorCustomFilter;
use App\Services\Filter\FacilitatorTypeCustomFilter;
use App\Services\Filter\FacilitatorApproveCustomFilter;
use Craftsys\Msg91\Facade\Msg91;
use App\Services\StudentOrganizationCustomSort;
use App\Services\StudentCentreCustomSort;
use App\Services\FacilitatorStateCustomSort;
use Spatie\QueryBuilder\AllowedSort;


/**
 * Class FacilitatorRepository
 * @package App\Repositories
 */
class FacilitatorRepository
{
    /**
     * List all facilitators
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request, User $user)
    {
        if (auth()->user()->hasPermissionTo('program.administrator')) {
            $facilitators = QueryBuilder::for(User::class)

                ->join('organisations', 'users.organisation_id', '=', 'organisations.id')

                ->join('organisation_program', 'organisations.id', '=', 'organisation_program.organisation_id')

                ->join('facilitator_details', 'facilitator_details.user_id', '=', 'users.id')

                ->select('users.*')

                ->where('organisation_program.program_id', auth()->user()->program_id);
        } elseif (auth()->user()->hasPermissionTo('project.administrator')) {
            $facilitators = QueryBuilder::for(User::class)

                ->join('centres', 'users.centre_id', '=', 'centres.id')

                ->join('centre_project', 'centres.id', '=', 'centre_project.centre_id')

                ->join('facilitator_details', 'facilitator_details.user_id', '=', 'users.id')

                ->select('users.*')

                ->where('centre_project.project_id', auth()->user()->project_id);
        } else {
            $facilitators = QueryBuilder::for(User::class)

                ->select('users.*')

                ->join('facilitator_details', 'facilitator_details.user_id', '=', 'users.id')

                ->when($user->hasPermissionTo('centre.administrator'), function ($query) {
                    return $query->where('centre_id', auth()->user()->centre_id);
                })
                ->when($user->hasPermissionTo('organisation.administrator'), function ($query) use ($user) {
                    return $query->where('organisation_id', $user->organisation_id);
                })
                ->when($user->hasPermissionTo('activity.needs.approval'), function ($query) use ($user) {
                    return $query->where('created_by', $user->id);
                })
                ->when(!$user->hasPermissionTo('activity.needs.approval'), function ($query) {
                    return $query->where('is_approved', 1);
                });
        }
        $facilitators =  $facilitators->where('users.tenant_id', getTenant())->where('type', User::TYPE_FACILITATOR);
        $totalCount = $facilitators->count();
        $facilitators = $facilitators->allowedFilters([
            'name', 'mobile', 'email',
            AllowedFilter::exact('centre_id'),
            AllowedFilter::scope('users.name'),
            AllowedFilter::custom('search_value', new FacilitatorCustomFilter()),
            AllowedFilter::custom('facilitator_type', new FacilitatorTypeCustomFilter()),
            AllowedFilter::custom('is_approved', new FacilitatorApproveCustomFilter()),
            AllowedFilter::exact('gender'),
            AllowedFilter::exact('organisation_id'),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('users.created_platform'),
            AllowedFilter::exact('centre.state_id'),
            AllowedFilter::exact('centre.centre_type_id'),
            AllowedFilter::scope('start_date'),
            AllowedFilter::scope('end_date'),

        ])
            ->defaultSort('-created_at')
            ->with('facilitatorDetail')
            ->allowedSorts(['name', 'mobile', 'email', 'status',
            AllowedSort::custom('organisation.name', new StudentOrganizationCustomSort()),
            AllowedSort::custom('state.name', new FacilitatorStateCustomSort()),
            AllowedSort::custom('centre.name', new StudentCentreCustomSort())]);
        if (isset($request['limit'])) {
            $facilitators = $facilitators->paginate($request['limit'] ?? null);
        } else {
            $facilitators = $facilitators->get();
        }
        return ['facilitators' => $facilitators, 'total_count' => $totalCount];
    }
    /**
     * Create a new Facilitator
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request, $user)
    {
        $userId = $user->id;
        if ($user->hasPermissionTo('activity.needs.approval')) {
            $user = new User();
            $user = $this->setUser($request, $user);
            $user->is_approved = User::TYPE_NOT_APPROVED;
            $user->created_by = $userId;
            $user->created_platform = User::CREATED_PLATFORM_ADMIN;
            $user->save();
            $setUserPhase = setUserPhase($user);

            $facilitator = new FacilitatorDetail();
            $facilitator = $this->setFacilitator($request, $facilitator, $user->id);
            $facilitator->user_approved = 1;
            $facilitator->save();
            $superfacilitatorStatus = $request['is_super_facilitator'];
            $masterTrainerStatus = $request['is_master_trainer'];
            $this->assignRoles($user, $superfacilitatorStatus, $masterTrainerStatus, $request['centre_id']);

            $approval = new Approval();
            $approval = $this->setApproval($approval, $user);
            $approval->save();
        } else {
            $user = new User();
            $user = $this->setUser($request, $user);
            $user->created_by = $userId;
            $user->created_platform = User::CREATED_PLATFORM_ADMIN;
            $user->save();
            $setUserPhase = setUserPhase($user);

            $facilitator = new FacilitatorDetail();
            $facilitator = $this->setFacilitator($request, $facilitator, $user->id);
            $facilitator->user_approved = 1;
            $facilitator->save();
            $superfacilitatorStatus = $request['is_super_facilitator'];
            $masterTrainerStatus = $request['is_master_trainer'];
            $this->assignRoles($user, $superfacilitatorStatus, $masterTrainerStatus, $request['centre_id']);
        }
        if ($request['email'] != "") {
            $this->setEmailVerify($request, $user->id);
        }
        return $user;
    }

    /**
     * Delete a Facilitator
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy($user)
    {
        MasterTrainerUser::where('user_id', $user->id)->delete();
        $user->facilitatorDetail->delete();
        $user->delete();
    }

    /**
     * Update Facilitator
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function update($request, $user)
    {
        $superfacilitatorStatus = $request['is_super_facilitator'];
        $masterTrainerStatus = $request['is_master_trainer'];
        if ($request['is_master_trainer'] == User::NOT_MASTER_TRAINER) {
            $user->masterTrainerUsers()->detach();
        }
        if ($superfacilitatorStatus == 1) {
            if ($user->organisation_id != $request['organisation_id']) {
                $user->centres()->detach();
            }
        }
        $user = $this->setUser($request, $user);
        $user->update();
        $setUserPhase = setUserPhase($user);

        $facilitator = $user->facilitatorDetail;
        $facilitator = $this->setFacilitator($request, $facilitator, $user->id);
        $facilitator->update();
        $this->assignRoles($user, $superfacilitatorStatus, $masterTrainerStatus, $request['centre_id']);
        return $user;
    }

    /**
     * Set user Data
     * @param mixed $request
     * @param mixed $user
     *
     * @return [collection]
     */
    private function setUser($request, $user)
    {
        $authUser = auth()->user();
        $user->name = $request['name'];
        $user->gender = $request['gender'];
        $user->email = $request['email'];
        $user->mobile = $request['mobile'];
        if ($request['password'] != "") {
            $user->password = Hash::make($request['password']);
        }
        $user->type = User::TYPE_FACILITATOR;
        $user->organisation_id = $request['organisation_id'];
        $user->centre_id = $request['centre_id'];
        $user->is_super_facilitator = isset($request['is_super_facilitator']) ?
            ($request['is_super_facilitator'] ?: null) : null;
        $user->is_master_trainer = isset($request['is_master_trainer']) ?
            ($request['is_master_trainer'] ?: null) : null;
        $user->tenant_id = getTenant();
        $user->created_by = $authUser->id;
        return $user;
    }

    /**
     * Set facilitator Data
     * @param mixed $request
     * @param mixed $facilitator
     *
     * @return [collection]
     */
    private function setFacilitator($request, $facilitator, $userId)
    {
        $facilitator->user_id = $userId;
        $facilitator->designation = $request['designation'];
        $facilitator->date_of_birth = isset($request['date_of_birth']) ? ($request['date_of_birth'] ?: null) : null;
        $facilitator->qualification = $request['qualification'];
        $facilitator->experience = $request['experience'];
        return $facilitator;
    }
    /**
     * Set Email Verify Data
     * @param mixed $request
     * @param mixed $userId
     *
     * @return [collection]
     */
    private function setEmailVerify($request, $userId)
    {
        $emailVerify = new EmailVerify();
        $emailVerify->user_id = $userId;
        $emailVerify->email = $request['email'];
        $emailVerify->save();
        return $emailVerify;
    }
    /**
     * Set Approval Data
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [collection]
     */
    private function setApproval($approval, $user)
    {
        $approval->type = Approval::TYPE_SINGLE;
        $approval->title = trans('admin.facilitator_approval_created');
        $approval->reference_id = $user->id;
        $approval->model = Approval::TYPE_FACILITATOR_MODEL;
        $approval->status = Approval::TYPE_NEEDS_APPROVAL;
        return $approval;
    }


    /**
     * Export facilitators
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function exportFacilitator($request)
    {
        $fileName = "facilitator_downlods/" . time() . "facilitators.csv";
        Excel::store(new FacilitatorsExport(), $fileName, 's3');
        return generateTempUrl($fileName);
    }

    /**
     * Import facilitators
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function importFacilitator($request, $userId)
    {
        $approvalConst = $this->approvalConstCreate();
        $import = new FacilitatorImport($approvalConst, $userId);
        Excel::import($import, $request['facilitator_upload_file']);
        return $import->data;
    }

    public function approvalConstCreate()
    {
        $approvalConst = Str::uuid();
        $approval = Approval::where('reference_id', $approvalConst)->first();
        if ($approval) {
            return $this->approvalConstCreate();
        }
        return $approvalConst;
    }

    /**
     * Assign Roles
     *
     * @param mixed $facilitator
     * @param mixed $superfacilitatorStatus
     * @param mixed $centreId
     *
     * @return [type]
     */

    private function assignRoles($facilitator, $superfacilitatorStatus, $masterTrainerStatus, $centreId)
    {
        if ($superfacilitatorStatus == 1) {
            $facilitator->syncRoles(["superfacilitator", "facilitator"]);
            $this->assignCentres($facilitator, $centreId, 1);
        } else {
            $facilitator->syncRoles("facilitator");
            $facilitator->centres()->detach();
        }
        if ($masterTrainerStatus == 1) {
            $facilitator->assignRole("mastertrainer");
        } else {
            $facilitator->removeRole("mastertrainer");
        }
    }

    /**
     * Assign Centres to super facilitator
     *
     * @param mixed $facilitator
     * @param mixed $centreIds
     *
     * @return [type]
     */
    public function assignCentres($facilitator, $centreIds, $type)
    {
        if ($type == 1) {
            $facilitator->centres()->syncWithoutDetaching($centreIds);
        } else {
            $facilitator->centres()->sync($centreIds);
        }
    }

    /**
     * Assign facilitators to master trainer
     *
     * @param mixed $mastertrainer
     * @param mixed $facilitatorIds
     *
     * @return [type]
     */
    public function assignFacilitators($facilitator, $facilitatorIds)
    {
        $facilitatorSameCentreIds = [];
        $masterTrainer = $facilitator->is_master_trainer;
        if (($masterTrainer == USER::MASTER_TRAINER)) {
            $masterTrainerFacilitator = $facilitator->masterTrainerUsers()->pluck('user_id')->toArray();
            $nonAssignedFacilitator = array_diff($facilitatorIds, $masterTrainerFacilitator);
            FacilitatorDetail::whereIn('user_id', $nonAssignedFacilitator)
                ->update(['batch_id' => null]);

            foreach ($facilitatorIds as $facilitatorId) {
                $facilitatorIdDetail = User::where('id', $facilitatorId)
                    ->where('type', User::TYPE_FACILITATOR)->first();
                if ($facilitatorIdDetail->id != $facilitator->id) {
                    array_push($facilitatorSameCentreIds, $facilitatorIdDetail->id);
                }
            }
            $facilitator->masterTrainerUsers()->sync($facilitatorSameCentreIds);
        }
    }

    /**
     * List facilitators to master trainer
     *
     * @param mixed $facilitatorr
     *
     * @return [type]
     */
    public function listFacilitatorsForMasterTrainers($request, $masterTrainer)
    {

        $otherTaggedFacilitators = MasterTrainerUser::where('master_trainer_id', '<>', $masterTrainer->id)
            ->distinct()->pluck('user_id')->toArray();
        $facilitators = QueryBuilder::for(User::class)
            ->allowedFilters([
                'name',
                AllowedFilter::exact('centre_id'),
                AllowedFilter::exact('organisation_id'),
                AllowedFilter::exact('centre.state_id'),

            ])
            ->where('tenant_id', getTenant())->where('type', User::TYPE_FACILITATOR)
            ->where(function ($query) {
                $query->where('is_master_trainer', User::NOT_MASTER_TRAINER);
                $query->orwhereNull('is_master_trainer');
            })
            ->whereNotIn('id', $otherTaggedFacilitators)
            ->latest();
        if (isset($request['limit'])) {
            $facilitators = $facilitators->paginate($request['limit'] ?? null);
        } else {
            $facilitators = $facilitators->get();
        }
        return $facilitators;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function paginate($items, $perPage, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    /**
     * Map facilitators to master trainer  data
     *
     * @return Array
     */
    public static function getData($facilitators, $masterTrainer)
    {
        $selectedFacilitators = $masterTrainer->masterTrainerUsers()->pluck('user_id')->toArray();
        $otherTaggedFacilitators = MasterTrainerUser::distinct()->pluck('user_id')->toArray();
        $facilitators = $facilitators->map(function ($facilitator) use (
            $selectedFacilitators,
            $otherTaggedFacilitators,
            $masterTrainer
        ) {
            $facilitator->masterTrainerId = $masterTrainer->id;
            if (in_array($facilitator->id, $selectedFacilitators)) {
                $facilitator->selectedFacilitator = true;
            } elseif (!in_array($facilitator->id, $otherTaggedFacilitators)) {
                $facilitator->selectedFacilitator = false;
            }
            return $facilitator;
        });
        return $facilitators;
    }
    /**
     * @param mixed $request
     * @param mixed $lesson
     *
     * @return [type]
     */
    public function updateStatus($request, $user)
    {

        $user->status = $request['status'];
        $user->update();
        return $user;
    }

    /**
     * Send OTP through mobile
     *
     * @param mixed $user
     * @param mixed $message
     */
    private function sendMessage($userMobile)
    {
        Msg91::sms()
            ->to(USER::COUNTRY_PHONE_CODE . $userMobile) // set the mobile with country code
            ->flow(env('MSG91_FLOW_ID')) // set the flow id
            ->send(); // send
    }
    /**
     * Send approval message through email
     *
     * @param mixed $user
     * @param mixed $message
     */
    private function sendEmail($userEmail)
    {

        $domainEmail = env('MSG91_EMAIL');
        $templateName = "facilitaror-approval";
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
            CURLOPT_POSTFIELDS => "{\n  \"to\": [\n    {\n      \n      \"email\": \"$userEmail\"\n    }\n  ],\n  \"from\":  {\n    \"name\": \"Quest\",\n   \"email\": \"$domainEmail\"\n  },\n \n  \"domain\": \"$domain\",\n  \"mail_type_id\": \"1\",\n   \n \n  \"template_id\": \"$templateName\"\n  \n}",
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

    /**
     * @param mixed $request
     * @param mixed $user
     *
     * @return [type]
     */
    public function enableApproval($user)
    {
        if (($user->facilitatorDetail->user_approved) == 0) {
            $user->facilitatorDetail->user_approved = 1;
            $user->facilitatorDetail->update();
            if ($user->mobile) {
                $this->sendMessage($user->mobile);
            } else {
                $this->sendEmail($user->email);
            }
        }
        return $user;
    }
}
