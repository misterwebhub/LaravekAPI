<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Repositories\v1\UserRepository;
use Illuminate\Http\Request;
use App\Http\Requests\v1\AdminUserRequest;
use App\Http\Requests\v1\UserFileForDeleteRequest;

class UserController extends Controller
{
    private $userRepository;
    /**
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->middleware(
            'role:super-admin',
            ['only' => ['index', 'exportAdminUser', 'importAdminUserWithRoles']]
        );
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $users = $this->userRepository->index($request->all());
        return UserResource::collection($users)
            ->additional(['total_without_filter' => User::where('type', User::TYPE_ADMIN)->count()]);
    }
    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function exportAdminUser(Request $request)
    {
        $filePath = $this->userRepository->exportAdminUser($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);
    }

    /**
     * @param AdminUserRequest $request
     *
     * @return [type]
     */
    public function importAdminUserWithRoles(AdminUserRequest $request)
    {
        $filePath = $this->userRepository->importAdminUserWithRoles($request->all());
        return response([
            'data' => $filePath,
        ], 200);
    }
    public function bulkUserDelete(UserFileForDeleteRequest $request)
    {

        $filePath = $this->userRepository->bulkUserDelete($request->all());
        return response([
            'data' => $filePath,
        ], 200);
    }
    
}
