<?php

namespace App\Repositories\v1;

use App\Models\StudentDetail;
use App\Models\User;
use App\Models\Approval;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentExport;
use App\Models\Centre;
use App\Models\EmailVerify;
use App\Models\Batch;
use App\Services\Filter\StudentCustomFilter;
use App\Services\StudentOrganizationCustomSort;
use App\Services\StudentCentreCustomSort;
use Spatie\QueryBuilder\AllowedSort;
use App\Jobs\StudentsReportCreated;

/**
 * Class StudentRepository
 * @package App\Repositories
 */
class StudentRepository
{
    /**
     * List all students
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request, User $user)
    {
        if ($user->hasPermissionTo('program.administrator')) {
            $students = QueryBuilder::for(User::class)

                ->join('organisations', 'users.organisation_id', '=', 'organisations.id')

                ->join('organisation_program', 'organisations.id', '=', 'organisation_program.organisation_id')

                ->select('users.*')

                ->where('organisation_program.program_id', $user->program_id);
        } elseif ($user->hasPermissionTo('project.administrator')) {
            $students = QueryBuilder::for(User::class)

                ->join('centres', 'users.centre_id', '=', 'centres.id')

                ->join('centre_project', 'centres.id', '=', 'centre_project.centre_id')

                ->select('users.*')

                ->where('centre_project.project_id', $user->project_id);
        } else {
            $students = QueryBuilder::for(User::class)
                ->when($user->hasPermissionTo('centre.administrator'), function ($query) use ($user) {
                    return $query->where('centre_id', $user->centre_id);
                })
                ->when($user->hasPermissionTo('organisation.administrator'), function ($query) use ($user) {
                    return $query->where('organisation_id', $user->organisation_id);
                })
                ->when($user->hasPermissionTo('activity.needs.approval'), function ($query) use ($user) {
                    return $query->where('created_by', $user->id);
                })
                ->when(!$user->hasPermissionTo('activity.needs.approval'), function ($query) {
                    return $query->where('users.is_approved', 1);
                });
        }
        $students = $students->where('users.tenant_id', getTenant())
            ->whereIn('type', [User::TYPE_STUDENT, User::TYPE_ALUMNI]);
        $totalCount = $students->count();
        $students = $students
            ->allowedFilters([
                AllowedFilter::exact('gender'), AllowedFilter::exact('type'),
                AllowedFilter::exact('organisation_id'), AllowedFilter::exact('centre_id'),
                AllowedFilter::exact('status'), AllowedFilter::exact('created_platform'),
                AllowedFilter::exact('centre.state_id'), AllowedFilter::exact('centre.centre_type_id'),
                AllowedFilter::exact('centre.working_mode'), AllowedFilter::scope('start_date'),
                AllowedFilter::scope('end_date'), AllowedFilter::scope('project')->ignore(null),
                AllowedFilter::custom('search_value', new StudentCustomFilter()),
            ])
            ->defaultSort('-created_at')
            ->allowedSorts(
                [
                    'name', 'email', 'mobile', 'status',
                    AllowedSort::custom('organisation.name', new StudentOrganizationCustomSort()),
                    AllowedSort::custom('centre.name', new StudentCentreCustomSort()),
                ]
            );
        if (isset($request['limit'])) {
            $students = $students->paginate($request['limit'] ?? null);
        } else {
            $students = $students->get();
        }
        return ['students' => $students, 'total_count' => $totalCount];
    }
    /**
     * Create a new student
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

            $user->type = User::TYPE_STUDENT;
            $user->created_platform = User::CREATED_PLATFORM_ADMIN;
            $batchId = ($request['batch_id']) ?? null;
            if ($batchId) {
                $batchDet = Batch::find($batchId);
                if ($batchDet && $batchDet->status == Batch::TYPE_ALUMINI) {
                    $user->type = User::TYPE_ALUMNI;
                }
            }
            $user->is_approved = User::TYPE_NOT_APPROVED;
            $user->created_by = $userId;
            $user->save();
            if ($batchId) {
                setUserPhase($user, $batchId);
            }else{
                setUserPhase($user);
            }


            $student = new StudentDetail();
            $student = $this->setStudent($request, $student, $user->id);
            $student->batch_id = isset($request['batch_id']) ?
                ($request['batch_id'] ?: null) : null;
            $student->save();
            $user->syncRoles("student");
            $approval = new Approval();
            $approval = $this->setApproval($approval, $user);
            $approval->save();
        } else {
            $user = new User();
            $user = $this->setUser($request, $user);

            $user->type = User::TYPE_STUDENT;
            $batchId = ($request['batch_id']) ?? null;
            if ($batchId) {
                $batchDet = Batch::find($batchId);
                if ($batchDet && $batchDet->status == Batch::TYPE_ALUMINI) {
                    $user->type = User::TYPE_ALUMNI;
                }
            }
            $user->created_platform = User::CREATED_PLATFORM_ADMIN;
            $user->created_by = $userId;
            $user->save();

            if ($batchId) {
                setUserPhase($user, $batchId);
            } else {
                setUserPhase($user);
            }



            $student = new StudentDetail();
            $student = $this->setStudent($request, $student, $user->id);
            $student->batch_id = isset($request['batch_id']) ?
                ($request['batch_id'] ?: null) : null;
            $student->save();
            $user->syncRoles("student");
        }
        if ($request['email'] != "") {
            $this->setEmailVerify($request, $user->id);
        }
        return $user;
    }

    /**
     * Delete a Student
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy($user)
    {
        $user->studentDetail->delete();
        removeUserPhase($user);
        $user->delete();
    }

    /**
     * Update Student
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function update($request, $user)
    {
        $oldCentreId = $user->centre_id;
        $user = $this->setUser($request, $user);

        $user->update();
        $setUserPhase = setUserPhase($user);

        $student = $user->StudentDetail;
        $student = $this->setStudent($request, $student, $user->id);
        if ($oldCentreId != $request['centre_id']) {
            $student->batch_id = null;
        }
        $student->update();
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
        $user->name = $request['name'];
        $user->gender = $request['gender'];
        $user->email = $request['email'];
        $user->mobile = $request['mobile'];
        if ($request['password'] != "") {
            $user->password = Hash::make($request['password']);
        }
        $user->organisation_id = $request['organisation_id'];
        $user->centre_id = $request['centre_id'];
        $user->tenant_id = getTenant();
        return $user;
    }

    /**
     * Set student Data
     * @param mixed $request
     * @param mixed $student
     *
     * @return [collection]
     */
    private function setStudent($request, $student, $userId)
    {
        $student->user_id = $userId;
        $student->date_of_birth = isset($request['date_of_birth']) ? ($request['date_of_birth'] ?: null) : null;
        $student->educational_qualification_id = isset($request['qualification']) ?
            ($request['qualification'] ?: null) : null;
        $student->guardian_name = $request['guardian_name'];
        $student->guardian_email = $request['guardian_email'];
        $student->guardian_mobile = $request['guardian_mobile'];
        $student->guardian_income = isset($request['guardian_income']) ? ($request['guardian_income'] ?: null) : null;
        $student->guardian_occupation = isset($request['guardian_occupation']) ?
            ($request['guardian_occupation'] ?: null) : null;
        $student->experience = $request['experience'];
        $student->trade_id = isset($request['trade_id']) ? ($request['trade_id'] ?: null) : null;
        $student->placement_status_id = isset($request['placement_status']) ?
            ($request['placement_status'] ?: null) : null;
        return $student;
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
        $approval->title = trans('admin.learner_approval_created');
        $approval->reference_id = $user->id;
        $approval->model = Approval::TYPE_LEARNER_MODEL;
        $approval->status = Approval::TYPE_NEEDS_APPROVAL;
        return $approval;
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
     * @param mixed $request
     * @param mixed $user
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
     * @param mixed $request
     *
     * @return [type]
     */
    public function getOrganisations($project, $user)
    {
        $orgDetails = [];
        foreach ($project->centres as $key => $centre) {
            $orgDetails[$key]['id'] = $centre->organisation_id;
            $orgDetails[$key]['name'] = $centre->organisation->name;
        }
        if ($user->hasPermissionTo('program.administrator')) {
            $proOrgDetailsAll = [];
            if ($user->program->organisation) {
                $organisationIds = $user->program->organisation->pluck('id')->toArray();
                foreach ($orgDetails as $org) {
                    if (in_array($org['id'], $organisationIds)) {
                        $proOrgDetails['id'] = $org['id'];
                        $proOrgDetails['name'] = $org['name'];
                        array_push($proOrgDetailsAll, $proOrgDetails);
                    }
                }
                return $proOrgDetailsAll;
            }
        }
        return $orgDetails;
    }

    /**
     * Export students in an organisation/centre
     *
     * @return [type]
     */
    public function exportStudent($request, $user)
    {
        $filter['centre.administrator'] = $user->hasPermissionTo('centre.administrator');
        $filter['organisation.administrator'] = $user->hasPermissionTo('organisation.administrator');
        $filter['activity.needs.approval'] = $user->hasPermissionTo('activity.needs.approval');
        $filter['program.administrator'] = $user->hasPermissionTo('program.administrator');
        $filter['project.administrator'] = $user->hasPermissionTo('project.administrator');
        $filter['project_id'] = $request['filter']['project_id'] ?? null;
        $filter['centre_id'] = $request['filter']['centre_id'] ?? null;
        $filter['organisation_id'] = $request['filter']['organisation_id'] ?? null;
        $filter['type'] = 3;
        $fileName = "student_downlods/" . time() . "students.csv";
        Excel::store(new StudentExport($filter), $fileName, 's3');
        return generateTempUrl($fileName);
    }

    /**
     *  Get all Student data count on filtering
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function studentCount($request, User $user)
    {
        $registerQuestMobile = $this->getUsers($request, $user)
            ->whereIn('created_platform', [User::CREATED_PLATFORM_MOBILEAPP])
            ->whereIn('type', [User::TYPE_STUDENT, User::TYPE_ALUMNI])->count();
        $registerQuestWeb = $this->getUsers($request, $user)
            ->whereIn('created_platform', [User::CREATED_PLATFORM_WEBAPP])
            ->whereIn('type', [User::TYPE_STUDENT, User::TYPE_ALUMNI])->count();
        $registerOnline = $this->getUsers($request, $user)->whereIn(
            'created_platform',
            [User::CREATED_PLATFORM_WEBAPP, User::CREATED_PLATFORM_MOBILEAPP]
        )
            ->whereIn('type', [User::TYPE_STUDENT, User::TYPE_ALUMNI])->count();
        $registerLearnPi = $this->getUsers($request, $user)
            ->whereIn('created_platform', [User::CREATED_PLATFORM_OFFLINE_APP])
            ->whereIn('type', [User::TYPE_STUDENT, User::TYPE_ALUMNI])->count();
        $registerCentreAdmin = $this->getUsers($request, $user)
            ->whereIn('created_platform', [User::CREATED_PLATFORM_CENTRE_ADMIN])
            ->whereIn('type', [User::TYPE_STUDENT, User::TYPE_ALUMNI])->count();
        $totalLearner = $this->getUsers($request, $user)
            ->whereIn('type', [User::TYPE_STUDENT])->count();
        $totalAlumini = $this->getUsers($request, $user)
            ->whereIn('type', [User::TYPE_ALUMNI])->count();
        return array(
            'register_via_quest_web' => $registerQuestWeb,
            'register_via_quest_mobile' => $registerQuestMobile,
            'register_via_online' => $registerOnline,
            'register_via_learn_pi' => $registerLearnPi,
            'register_via_centre_admin' => $registerCentreAdmin,
            'total_learner' => $totalLearner,
            'total_alumini' => $totalAlumini,
        );
    }

    private function getUsers($request, $user)
    {
        if ($user->hasPermissionTo('program.administrator')) {
            $students = QueryBuilder::for(User::class)

                ->join('organisations', 'users.organisation_id', '=', 'organisations.id')

                ->join('organisation_program', 'organisations.id', '=', 'organisation_program.organisation_id')

                ->select('users.*')

                ->where('organisation_program.program_id', $user->program_id);
        } elseif ($user->hasPermissionTo('project.administrator')) {
            $students = QueryBuilder::for(User::class)

                ->join('centres', 'users.centre_id', '=', 'centres.id')

                ->join('centre_project', 'centres.id', '=', 'centre_project.centre_id')

                ->select('users.*')

                ->where('centre_project.project_id', $user->project_id);
        } else {
            $students = QueryBuilder::for(User::class)
                ->when($user->hasPermissionTo('centre.administrator'), function ($query) use ($user) {
                    return $query->where('centre_id', $user->centre_id);
                })
                ->when($user->hasPermissionTo('organisation.administrator'), function ($query) use ($user) {
                    return $query->where('organisation_id', $user->organisation_id);
                });
        }
        $students = $students->allowedFilters([
            AllowedFilter::exact('gender'), AllowedFilter::exact('type'),
            AllowedFilter::exact('organisation_id'), AllowedFilter::exact('centre_id'),
            AllowedFilter::exact('status'), AllowedFilter::exact('created_platform'),
            AllowedFilter::exact('centre.state_id'), AllowedFilter::exact('centre.centre_type_id'),
            AllowedFilter::exact('centre.working_mode'), AllowedFilter::scope('start_date'),
            AllowedFilter::scope('end_date'), AllowedFilter::scope('project')->ignore(null),
            AllowedFilter::scope('name')
        ])->where('users.tenant_id', getTenant());
        return $students;
    }

    /**
     * @param mixed $request
     * @param mixed $user
     *
     * @return [type]
     */
    public function updateRegistrationStatus($request, $user)
    {
        $user->status = $request['status'];
        $user->update();
        return $user;
    }
}
