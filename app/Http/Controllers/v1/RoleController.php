<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\RoleRequest;
use App\Http\Requests\v1\RoleStatusRequest;
use App\Http\Requests\v1\RolePermissionImportRequest;
use App\Models\Role;
use App\Models\Permission;
use App\Repositories\v1\RoleRepository;
use App\Http\Requests\v1\RolePermissionRequest;
use App\Http\Resources\v1\RoleResource;
use App\Http\Resources\v1\RoleHasPermissionResource;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    private $roleRepository;

    /**
     * @param RoleRepository $roleRepository
     */
    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
        $this->middleware(
            'role:super-admin',
            ['only' => [
                'setPermissions', 'getPermissionForRoleType', 'index', 'store', 'show',
                'update', 'destroy', 'updateStatus', 'revokePermission', 'dynamicRolePermissions',
                'setPermission', 'exportRolePermissions', 'importRolePermissions'
            ]]
        );
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $roles = $this->roleRepository->index($request->all());
        return RoleResource::collection($roles)->additional(['total_without_filter' => Role::count()]);
    }

    /**
     * @param RoleRequest $request
     *
     * @return [type]
     */
    public function store(RoleRequest $request)
    {
        $roleNameCheck = $this->roleRepository->roleNameCheck($request->all());
        if ($roleNameCheck) {
            $data['message'] = trans('admin.data_invalid');
            $data['errors']['name'] = [trans('admin.name_exist')];
            return response()->json($data, 422);
        }
        $role = $this->roleRepository->store($request->all());
        return (new RoleResource($role))
            ->additional(['message' => trans('admin.role_added')]);
    }

    /**
     * @param mixed $role
     *
     * @return [type]
     */
    public function show(Role $role)
    {
        return new RoleResource($role);
    }

    /**
     * @param RoleRequest $request
     * @param mixed $role
     *
     * @return [type]
     */
    public function update(RoleRequest $request, Role $role)
    {
        $roleNameCheck = $this->roleRepository->roleNameCheck($request->all(), $role->id);
        if ($roleNameCheck) {
            $data['message'] = trans('admin.data_invalid');
            $data['errors']['name'] = [trans('admin.name_exist')];
            return response()->json($data, 422);
        }
        $role = $this->roleRepository->update($request->all(), $role);
        if ($role) {
            return (new RoleResource($role))
                ->additional(['message' => trans('admin.role_updated')]);
        } else {
            $data['message'] = trans('admin.data_invalid');
            $data['errors']['name'] = [trans('admin.user_exist')];
            return response()->json($data, 422);
        }
    }

    /**
     * @param mixed $role
     *
     * @return [type]
     */
    public function destroy(Role $role)
    {
        $roleDelete = $this->roleRepository->destroy($role);
        if ($roleDelete) {
            return response(['message' => trans('admin.role_deleted')], 200);
        } else {
            $data['message'] = trans('admin.data_invalid');
            $data['errors']['name'] = [trans('admin.user_exist')];
            return response()->json($data, 422);
        }
    }

    /**
     * @param Request $request
     * @param mixed $role
     *
     * @return [type]
     */
    public function updateStatus(RoleStatusRequest $request, Role $role)
    {
        $role = $this->roleRepository->updateStatus($request->all(), $role);
        return (new RoleResource($role))
            ->additional(['message' => trans('admin.role_status_change')]);
    }

    /**
     * @param Request $request
     * @param mixed $role
     *
     * @return [type]
     */
    public function setPermission(Role $role, Request $request)
    {
        $role = $this->roleRepository->SetPermission($request->all(), $role);
        return (new RoleResource($role))
            ->additional(['message' => trans('admin.permission_added')]);
    }

    /**
     *
     * @return [json]
     */
    public function getPermissionForRoleType(Request $request)
    {
        $permission = $this->roleRepository->getPermissionForRoleType($request->all());
        return RoleHasPermissionResource::collection($permission);
    }

    /**
     * @param Request $request
     * @param mixed $role
     *
     * @return [type]
     */
    public function revokePermission(Role $role, Request $request)
    {
        $role = $this->roleRepository->revokePermission($request->all(), $role);
        return (new RoleResource($role))
            ->additional(['message' => trans('admin.permission_revoked')]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function dynamicRolePermissions(Request $request)
    {
        $roles = Permission::where('type', Permission::PERMISSION_TYPE_ONE)->get();
        return ['data' => $roles];
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */

    public function setPermissions(RolePermissionRequest $request)
    {
        $role = $this->roleRepository->setPermissions($request->all());
        return (new RoleResource($role))
            ->additional(['message' => trans('admin.permission_updated')]);
    }
    /**
     * @return [type]
     */
    public function exportRolePermissions()
    {
        $filePath = $this->roleRepository->exportRolePermissions();
        return response([
            'file_path' => $filePath,
        ], 200);
    }
    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function importRolePermissions(RolePermissionImportRequest $request)
    {
        $importData = $this->roleRepository->importRolePermissions($request->all());
        return response([
            'data' => $importData,
        ], 200);
    }
}
