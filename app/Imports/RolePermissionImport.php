<?php

namespace App\Imports;

use App\Exports\RolePermissionSuccessErrorExport;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class RolePermissionDataImport
 * @package App\Imports
 */
class RolePermissionImport implements ToCollection, WithHeadingRow
{
    public $data;
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $finalResultAll = [];
        $finalResultErrors = [];
        $keyArray = array(
            0 => '',
        );
        $roles = Role::with('permissions')->where('type', 1)->get();
        foreach ($roles as $role) {
            $keyArray[] = str_replace('-', '_', $role->name);
        }

        DB::beginTransaction();
        $roleCollections = $collection->first();
        if (!empty($roleCollections)) {
            foreach ($roleCollections as $key => $value) {
                $this->revokeRolePermissions($key);
            }
        }
        $errorCount = 0;
        foreach ($collection->chunk(10) as $chunk) {
            foreach ($chunk as $row) {
                $finalRes = [];
                $this->checkExcelFormat($row, $keyArray);
                foreach ($row as $key => $value) {
                    $finalRes[] = (string) $value;
                }
                $errors = $this->validateFields($row);
                if (count($errors) == 0) {
                    try {
                        $this->setRolePermissions($row);
                        DB::commit();
                        $finalRes[] = "Success";
                        $finalResultAll[] = $finalRes;
                    } catch (\Exception $e) {
                        DB::rollback();
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
        $errorFileName = 'permission_downlods/error/' . 'error_' . $uniqid . '.csv';
        $fileName = 'permission_downlods/' . $uniqid . '.csv';
        Excel::store(new RolePermissionSuccessErrorExport($export), $fileName, 's3');
        Excel::store(new RolePermissionSuccessErrorExport($errorExport), $errorFileName, 's3');
        $data['status'] = 1;
        $data['message'] = trans('admin.file_imported');
        if ($errorCount == 0) {
            $data['error_status'] = 0;
        } else {
            $data['error_status'] = 1;
            $data['error_file_name'] = Storage::url($errorFileName);
        }
        $data['uploaded_file_name'] = Storage::url($fileName);
        $this->data = $data;
    }

    private function checkExcelFormat($row, $keyArray)
    {
        $i = 0;
        foreach ($row as $key => $value) {
            if (!in_array($key, $keyArray)) {
                throw ValidationException::withMessages(
                    array("file" =>
                    "Invalid Excel Format," . ucfirst(str_replace('_', ' ', $keyArray[$i])) . " Role Missing")
                );
            }
            $i++;
        }
    }
    private function validateFields($roleDetails)
    {
        $errors = [];
        $array = $roleDetails->toArray();

        $permissionGroup = $array[array_key_first($array)];
        if ($permissionGroup != 'Miscellaneous') {
            $permission = Permission::where('sub_group', $permissionGroup)
                ->orwhere('title', $permissionGroup)
                ->first();
            if (!$permission) {
                $errors[] = trans('admin.invalid_permission');
            }
        }
        return $errors;
    }
    private function revokeRolePermissions($role)
    {
        $rolename = str_replace('_', '-', $role);
        $role = Role::where('name', $rolename)->where('type', 1)->first();
        if (!empty($role)) {
            $rolePermissions = $role->permissions->where('type', 0)->pluck('name')->toArray();
            $role->revokePermissionTo($rolePermissions);
        }
        return $role;
    }
    private function setRolePermissions($row)
    {
        $array = $row->toArray();
        $permissionGroup = $array[array_key_first($array)];
        $permission = Permission::where('sub_group', $permissionGroup)
            ->orwhere('title', $permissionGroup)
            ->first();
        if ($permission) {
            foreach ($row as $key => $value) {
                $rolename = str_replace('_', '-', $key);
                $role = Role::where('name', $rolename)->where('type', 1)->first();
                if (!empty($role)) {
                    if ($value == 'Write' || $value == 'write') {
                        $role->givePermissionTo($this->createPermission($permissionGroup));
                        $role->givePermissionTo($this->updatePermission($permissionGroup));
                        $role->givePermissionTo($this->viewPermission($permissionGroup));
                        $role->givePermissionTo($this->destroyPermission($permissionGroup));
                    } elseif ($value == 'Read' || $value == 'read') {
                        $role->givePermissionTo($this->viewPermission($permissionGroup));
                    } elseif ($value == 'Yes' || $value == 'yes') {
                        $role->givePermissionTo($this->miscellaneousPermission($permissionGroup));
                    }
                }
            }
        }
        return $row;
    }
    private function createPermission($permissionGroup)
    {
        $permission = Permission::where('sub_group', $permissionGroup)
            ->where('group', Permission::PERMISSION_CREATE)
            ->first();
        if ($permission) {
            return $permission->name;
        }
    }
    private function updatePermission($permissionGroup)
    {
        $permission = Permission::where('sub_group', $permissionGroup)
            ->where('group', Permission::PERMISSION_UPDATE)
            ->first();
        if ($permission) {
            return $permission->name;
        }
    }
    private function viewPermission($permissionGroup)
    {
        $permission = Permission::where('sub_group', $permissionGroup)
            ->where('group', Permission::PERMISSION_READ)
            ->first();
        if ($permission) {
            return $permission->name;
        }
    }

    private function destroyPermission($permissionGroup)
    {
        $permission = Permission::where('sub_group', $permissionGroup)
            ->where('group', Permission::PERMISSION_DELETE)
            ->first();
        if ($permission) {
            return $permission->name;
        }
    }
    private function miscellaneousPermission($permissionGroup)
    {
        $permission = Permission::where('title', $permissionGroup)
            ->where('group', Permission::PERMISSION_MISCELLANEOUS)
            ->first();
        if ($permission) {
            return $permission->name;
        }
    }
}
