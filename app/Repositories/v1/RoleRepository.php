<?php

namespace App\Repositories\v1;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Spatie\QueryBuilder\QueryBuilder;
use App\Exports\RolePermissionExport;
use App\Imports\RolePermissionImport;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

/**
 * [Description RoleRepository]
 */
class RoleRepository
{
    /**
     * List all roles
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request)
    {
        $roles = QueryBuilder::for(Role::class)
            ->allowedFilters(['name', 'status'])
            ->allowedSorts(['name', 'status'])
            ->where('type', Role::TYPE_ADMIN)
            ->where('tenant_id', getTenant())
            ->latest()
            ->paginate($request['limit'] ?? null);
        return $roles;
    }

    /**
     * Get permission list
     *
     * @param mixed $permission
     *
     * @return [type]
     */
    public function getPermissionForRoleType($request)
    {
        $data = [];
        $permissions = Permission::where('type', 0)->get();
        $role = Role::where('name', $request['role_name'])->first();
        foreach ($permissions as $permission) {
            $hasPermissionArray['role_id'] = $role->id;
            $hasPermissionArray['role_name'] = $role->name;
            $hasPermissionArray['permission_id'] = $permission->id;
            $hasPermissionArray['permission_name'] = $permission->name;
            $hasPermissionArray['permission_title'] = $permission->title;
            $hasPermissionArray['permission_group'] = $permission->group;
            $hasPermissionArray['permission_parent_group'] = $permission->parent_group;
            $hasPermissionArray['permission_sub_group'] = $permission->sub_group;
            $hasPermissionArray['role_has_permission'] = $role->hasPermissionTo($permission);
            array_push($data, $hasPermissionArray);
        };
        return $data;
    }

    /**
     * Create a new Role
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request)
    {
        $role = new Role();
        $role->status = $request['status'] ?? Role::INACTIVE_STATUS;
        $role = $this->setRole($request, $role);
        $role->save();
        $role = $this->setPermission($request, $role);
        if ($request['need_approval'] == 1) {
            $role->givePermissionTo('activity.needs.approval');
        } else {
            $role->revokePermissionTo('activity.needs.approval');
        }
        $role->givePermissionTo('menu.analytics');
        $role->givePermissionTo('menu.analytics.userdemographics');
        return $role;
    }

    /**
     * Delete a Role
     * @param mixed $Role
     *
     * @return [type]
     */
    public function destroy($role)
    {
        $users = User::role($role->name)->get();
        if ($users->count() > 0) {
            return null;
        } else {
            $role->permissions()->detach();
            return $role->delete();
        }
    }

    /**
     * Update Role
     * @param mixed $request
     * @param mixed $Role
     *
     * @return [json]
     */
    public function update($request, $role)
    {
        $roleName = strtolower(str_replace(' ', '-', trim($request['name'])));
        $rolePermision = $role->permissions()->where('type', 1)->first();
        if ($rolePermision) {
            if ($role->name != $roleName || $rolePermision->id != $request['permission_id']) {
                $users = User::role($role->name)->get();
                if ($users->count() > 0) {
                    return null;
                }
                $role->revokePermissionTo($rolePermision);
            }
        }
        $role = $this->setRole($request, $role);
        $role->update();
        $role = $this->setPermission($request, $role);
        if ($request['need_approval'] == 1) {
            $role->givePermissionTo('activity.needs.approval');
        } else {
            $role->revokePermissionTo('activity.needs.approval');
        }
        return $role;
    }

    /**
     * Update status of Role
     * @param mixed $request
     * @param mixed $role
     *
     * @return [type]
     */
    public function updateStatus($request, $role)
    {
        $role->status = $request['status'];
        $role->update();
        return $role;
    }

    /**
     * Set Role Data
     * @param mixed $request
     * @param mixed $role
     *
     * @return [collection]
     */
    private function setRole($request, $role)
    {
        $role->name = strtolower(str_replace(' ', '-', trim($request['name'])));
        $role->guard_name = Role::GUARD_NAME;
        $role->type = Role::TYPE_STATUS;
        $role->need_approval = $request['need_approval'];
        $role->description = $request['description'];
        $role->status = $request['status'] ?? null;
        $role->tenant_id = getTenant();
        return $role;
    }

    /**
     * Set Role Permission
     * @param mixed $request
     * @param mixed $role
     *
     * @return [collection]
     */
    public function setPermission($request, $role)
    {
        $permissionId = $request['permission_id'];
        $permission = Permission::find($permissionId);
        $role->givePermissionTo($permission);
        return $role;
    }

    /**
     * Revoke Role Permission
     * @param mixed $request
     * @param mixed $role
     *
     * @return [collection]
     */
    public function revokePermission($request, $role)
    {
        $permissionId = $request['permission_id'];
        $permission = Permission::find($permissionId);
        $role->permissions()->detach($permission);
        return $role;
    }

    /**
     * Set Role Permission
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function setPermissions($request)
    {

        $parentRole = array(
            "Institution" => "menu.institutions",
            "Content" => "menu.content",
            "Users" => "menu.users",
            "Analytics" => "menu.analytics"
        );
        $role = Role::where('name', $request['role'])->first();
        $rolePermissions = $role->permissions->where('type', 0)->pluck('name')->toArray();
        $roleParentGroupExisting = Permission::whereIn('name', $rolePermissions)
            ->whereNotNull('parent_group')->distinct()->pluck('parent_group')->toArray();
        foreach ($roleParentGroupExisting as $value) {
            if (array_key_exists($value, $parentRole)) {

                if ($value = $parentRole[$value]) {
                    $role->revokePermissionTo($value);
                } else {
                    $role->revokePermissionTo($parentRole);
                }
            }
        }


        $role->revokePermissionTo($rolePermissions);
        $roleParentGroup = Permission::whereIn('name', $request['permission'])
            ->whereNotNull('parent_group')->distinct()->pluck('parent_group')->toArray();




        foreach ($roleParentGroup as $value) {
            if (array_key_exists($value, $parentRole)) {

                if ($value = $parentRole[$value]) {
                    $role->givePermissionTo($value);
                } else {
                    $role->revokePermissionTo($parentRole);
                }
            }
        }
        $role->givePermissionTo($request['permission']);

        return $role;
    }

    /**
     * Check Role Name Exist or Not
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function roleNameCheck($request, $id = null)
    {
        $request['name'] = strtolower(str_replace(' ', '-', trim($request['name'])));
        if ($id) {
            $role = Role::where('name', $request['name'])->where('id', '!=', $id)->count();
        } else {
            $role = Role::where('name', $request['name'])->count();
        }
        return $role;
    }
    /**
     * Export role permissions
     *
     * @return [type]
     */
    public function exportRolePermissions()
    {
        $fileName = "permission_downlods/" . time() . "role_permissions.csv";
        $rolePermissions =  QueryBuilder::for(Role::class)
            ->where('type', 1)
            ->with('permissions')
            ->get();
        Excel::store(new RolePermissionExport($rolePermissions), $fileName, 's3');
        return generateTempUrl($fileName);
    }
    /**
     * Import role permissions
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function importRolePermissions($request)
    {
        $import = new RolePermissionImport();
        Excel::import($import, $request['role_upload_file']);
        return $import->data;
    }
}
