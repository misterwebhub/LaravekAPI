<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Role;
use App\Models\Centre;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Program;
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
use App\Exports\AdminUserSuccessErrorExport;

/**
 * Class adminUserToCentreImport
 * @package App\Imports
 */
class AdminUserImport implements ToCollection, WithStartRow
{
    public $data;


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
                $adminUserDetails = $this->fetchdata($row);
                foreach ($row as $value) {
                    $finalRes[] = $value;
                }
                $statusColumn = count($finalRes);
                if ($row[0]) {
                    $userId = (string)$row[0];
                    $user = User::where('id', $row[0])->first();
                    $errors = $this->validateFields((string)$row[0], $adminUserDetails);
                } else {
                    $user = new User();
                    $errors = $this->validateFields("", $adminUserDetails);
                }
                if (empty($errors)) {
                    $adminUserInformation =
                        $this->adminUserAddOrUpdate($user, $adminUserDetails, $row[0], $statusColumn, $finalRes);
                    $finalRes = $adminUserInformation['finalRes'];
                    $userId = $adminUserInformation['userId'];
                } else {
                    $errorCount++;
                    $finalRes[$statusColumn] = "adminUser details fail: " . implode(',', $errors);
                }

                $finalResultAll[] = $finalRes;
                if (!empty($errors)) {
                    $finalResultErrors[] = $finalRes;
                }
            }
        }
        $exportData = $this->exportData($finalResultAll, $finalResultErrors, $errorCount);
        $this->data = $exportData;
    }

    private function fetchdata($row)
    {
        $adminUserDetails['id'] = trim($row[0]);

        $adminUserDetails['name'] = trim($row[1]);
        $adminUserDetails['email'] = trim($row[2]) ?? null;
        $adminUserDetails['phone'] = trim($row[3]) ?? null;
        $adminUserDetails['role'] = trim($row[4]) ?? null;
        $adminUserDetails['type'] = trim($row[5]) ?? null;
        $roleName = str_replace(' ', '-', strtolower($adminUserDetails['role']));
        $rolesArray = array(
            'super-admin', 'quest-admin', 'job-uploader',
            'job-approver', 'community-facilitator', 'mastertrainer'
        );
        if (in_array($roleName, $rolesArray)) {
            $adminUserDetails['need_type'] = 'N';
        } else {
            $adminUserDetails['need_type'] = 'Y';
        }


        return $adminUserDetails;
    }


    private function validateFields($id, $adminUserDetails)
    {
        $errors = [];
        if ($id) {
            $errors = $this->userExistValidation($id, $adminUserDetails);
        } else {
            $errors = $this->userNotExistValidation($adminUserDetails);
        }
        if (empty($adminUserDetails['name'])) {
            $errors[] = trans('admin.adminUser_missing');
        }
        if (empty($adminUserDetails['role'])) {
            $errors[] = trans('admin.adminUser_missing');
        }
        if ($adminUserDetails['role']) {
            $roleExist = $this->roleNotExistValidation($adminUserDetails['role']);
            if (!$roleExist) {
                $errors[] = trans('admin.adminUser_exist');
            }
        }
        if (empty($adminUserDetails['type']) && ($adminUserDetails['need_type'] == 'Y')) {
            $errors[] = trans('admin.adminUser_missing');
        }

        if (empty($adminUserDetails['email']) && empty($adminUserDetails['phone'])) {
            $errors[] = trans('admin.either_email_mobile_required');
        }
        if (!empty($adminUserDetails['email'])) {
            if (!preg_match('/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/', $adminUserDetails['email'])) {
                $errors[] = trans('admin.invalid_email');
            }
        }
        if (!empty($adminUserDetails['phone'])) {
            if (!preg_match('/^[0-9]{10}+$/', $adminUserDetails['phone'])) {
                $errors[] = trans('admin.invalid_mobile_number');
            }
        }

        return $errors;
    }

    private function roleNotExistValidation($roleName = '')
    {
        $roleName = str_replace(' ', '-', strtolower($roleName));
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            return true;
        } else {
            return false;
        }
    }

    private function setUser($adminUserDetails, $user)
    {
        $adminUserDetails['password'] = "Quest@1234";
        $user->name = $adminUserDetails['name'];
        $user->email = $adminUserDetails['email'];
        $user->mobile = $adminUserDetails['phone'];
        if ($adminUserDetails['password'] != "") {
            $user->password = Hash::make($adminUserDetails['password']);
        }
        $user->type = User::TYPE_ADMIN;
        $user->created_platform = User::CREATED_PLATFORM_ADMIN;

        $user->tenant_id = getTenant();
        if (!empty($adminUserDetails['role'])) {
            $type =  $adminUserDetails['type'];
            $roleName = str_replace(' ', '-', strtolower($adminUserDetails['role']));
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $user->syncRoles($roleName);
            }
            switch ($role) {
                case $role->hasPermissionTo('centre.administrator'):
                    if (!empty($type)) {
                        $centre = Centre::where('name', $type)
                            ->where('deleted_at', '=', null)
                            ->first();
                        if ($centre) {
                            $data = $centre->id;
                            $user->centre_id = $data;
                        }
                    }
                    break;
                case $role->hasPermissionTo('organisation.administrator'):
                    if (!empty($type)) {
                        $organisation = Organisation::where('name', $type)
                            ->where('deleted_at', '=', null)
                            ->first();
                        if ($organisation) {
                            $data = $organisation->id;
                            $user->organisation_id = $data;
                        }
                    }
                    break;
                case $role->hasPermissionTo('project.administrator'):
                    if (!empty($type)) {
                        $project = Project::where('name', $type)
                            ->where('deleted_at', '=', null)
                            ->first();
                        if ($project) {
                            $data = $project->id;
                            $user->project_id = $data;
                        }
                    }
                    break;
                case $role->hasPermissionTo('program.administrator'):
                    if (!empty($type)) {
                        $program = Program::where('name', $type)
                            ->where('deleted_at', '=', null)
                            ->first();
                        if ($program) {
                            $data = $program->id;
                            $user->program_id = $data;
                        }
                    }
                    break;
            }
        }
        return $user;
    }





    private function exportData($finalResultAll, $finalResultErrors, $errorCount)
    {
        $export = collect($finalResultAll);
        $errorExport = collect($finalResultErrors);

        $uniqid = Str::random();
        $errorFileName = 'adminUser_centre_downlods/error/' . 'error_' . $uniqid . '.csv';
        $fileName = 'adminUser_centre_downlods/' . $uniqid . '.csv';
        Excel::store(new adminUserSuccessErrorExport($export), $fileName, 's3');
        Excel::store(new adminUserSuccessErrorExport($errorExport), $errorFileName, 's3');
        $data['status'] = 1;
        $data['message'] = trans('admin.file_imported');
        if ($errorCount == 0) {
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

    private function adminUserAddOrUpdate($user, $adminUserDetails, $id, $statusColumn, $finalRes)
    {
        $adminUserInformation = [];
        $adminUserInformation =  $adminUserDetails;
        if (!empty($id)) {
            $user = $this->setUser($adminUserDetails, $user);
            $user->update();
        } else {
            $user = new User();
            $user = $this->setUser($adminUserDetails, $user);
            $user->save();
        }


        DB::commit();
        $finalRes[$statusColumn] = "adminUser details success";
        $adminUserInformation['finalRes'] = $finalRes;
        $adminUserInformation['userId'] = $user->id;
        return $adminUserInformation;
    }


    private function userExistValidation($id, $adminUserDetails)
    {
        $errors = [];
        $emailCount = 0;
        $phoneCount = 0;
        $user = User::where('id', $id)->first();
        if (!$user) {
            $errors[] = trans('admin.invalid_id');
        }
        if (!empty($adminUserDetails['email'])) {
            $emailCount = User::where('email', $adminUserDetails['email'])
                ->WhereNull('deleted_at')
                ->where('id', '!=', $id)
                ->get()->count();
        }
        if (!empty($adminUserDetails['phone'])) {
            $phoneCount = User::where('mobile', $adminUserDetails['phone'])
                ->WhereNull('deleted_at')->where('id', '!=', $id)->get()->count();
        }
        if ($emailCount > 0) {
            $errors[] = trans('admin.adminUser_email_exist');
        }
        if ($phoneCount > 0) {
            $errors[] = trans('admin.adminUser_phone_exist');
        }

        return $errors;
    }
    private function userNotExistValidation($adminUserDetails)
    {
        $errors = [];
        $emailCount = 0;
        $phoneCount = 0;
        if (!empty($adminUserDetails['email'])) {
            $emailCount = User::where('email', $adminUserDetails['email'])
                ->WhereNull('deleted_at')->get()->count();
        }
        if (!empty($adminUserDetails['phone'])) {
            $phoneCount = User::where('mobile', $adminUserDetails['phone'])
                ->WhereNull('deleted_at')->get()->count();
        }

        if ($emailCount > 0) {
            $errors[] = trans('admin.adminUser_email_exist');
        }
        if ($phoneCount > 0) {
            $errors[] = trans('admin.adminUser_phone_exist');
        }

        return $errors;
    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }
}
