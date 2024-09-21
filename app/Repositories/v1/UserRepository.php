<?php

namespace App\Repositories\v1;

use App\Models\User;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Exports\AdminUserExport;
use Carbon\Carbon;
use App\Imports\AdminUserImport;
use Illuminate\Validation\ValidationException;
use App\Imports\UserDataForDeleteImport;

/**
 * Class UserRepository
 * @package App\Repositories
 */
class UserRepository
{
    /**
     * List all Users according to Role
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request)
    {
        return QueryBuilder::for(User::class)
            ->allowedFilters(['name', AllowedFilter::scope('role')->ignore(null)])
            ->where('tenant_id', getTenant())->where('type', User::TYPE_ADMIN)
            ->latest()
            ->paginate($request['limit'] ?? null);
    }
    /**
     * Export Admin User
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function exportAdminUser($request)
    {
        $fileName = "adminUser_downlods/" . Carbon::now()->format('YmdHs') . "adminUser.csv";

        Excel::store(new AdminUserExport(), $fileName, 's3');
        return generateTempUrl($fileName);
    }

    /**
     *Upload Admin User with Roles
     *
     * @return [type]
     */

    public function importAdminUserWithRoles($request)
    {
        $realPath = $request['admin_user_upload_file']->getRealPath();
        $validatedResult = checkAdminUserExcelFormat($realPath);
        if (!empty($validatedResult)) {
            $data['error'] = $validatedResult;
            throw ValidationException::withMessages(array($data));
        }
        $import = new AdminUserImport();
        Excel::import($import, $request['admin_user_upload_file']);
        return $import->data;
    }
    public function bulkUserDelete($request)
    {
        $import = new UserDataForDeleteImport();

        Excel::import($import, $request['user_delete_file']);
        return $import->data;
    }
}
