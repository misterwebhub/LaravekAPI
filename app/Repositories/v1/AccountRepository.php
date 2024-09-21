<?php

namespace App\Repositories\v1;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Notifications\AccountCreated;
use Illuminate\Support\Facades\DB;
use App\Services\Filter\AccountCustomFilter;

/**
 * Class AccountRepository
 * @package App\Repositories
 */
class AccountRepository
{
    /**
     * List all global accounts
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request)
    {
        return QueryBuilder::for(User::class)
            ->allowedFilters(
                [
                    'name', 'email', 'mobile', 'roles.name', AllowedFilter::scope('role')->ignore(null),
                    AllowedFilter::custom('search_value', new AccountCustomFilter()),
                ]
            )
            ->allowedSorts(
                ['name', 'email', 'mobile', 'status']
            )
            ->where('users.tenant_id', getTenant())->where('users.type', User::TYPE_ADMIN)
            ->latest('users.created_at')
            ->paginate($request['limit'] ?? null);
    }
    /**
     * Create a new Global Account
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request)
    {
        try {
            DB::beginTransaction();
            $role = Role::where('name', $request['account_type'])->first();
            $user = new User();
            $user = $this->setUser($request, $user);
            if ($user->is_quest_employee == User::IS_QUEST_EMPLOYEE) {
                $user->givePermissionTo('mqops.full.access');
            } else {
                $user->revokePermissionTo('mqops.full.access');
            }
            $user->save();
            $user->syncRoles($request['account_type']);
            $message = 'test content';
            $password = $request['password'];

            if ($role->hasPermissionTo('activity.needs.approval')) {
                $this->sendEmail($user, $message, $password);
            }
            DB::commit();
            return $user;
        } catch (\Exception $e) {
            DB::rollback();
            return array(
                'message' => trans('admin.operation failed')
            );
        }
    }

    /**
     * Send welcome mail
     *
     * @param mixed $user
     * @param mixed $message
     */
    private function sendEmail($user, $message, $password)
    {


        $domainEmail = env('MSG91_EMAIL');
        $templateName = "account-created-quest";
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
            CURLOPT_POSTFIELDS => "{\n  \"to\": [\n    {\n       \"email\": \"$user->email\"\n    }\n  ],\n  \"from\": {\n    \"name\": \"Quest\",\n    \"email\": \"$domainEmail\"\n  },\n \n  \"domain\": \"$domain\",\n  \"mail_type_id\": \"1  \",\n   \n  \"template_id\": \"$templateName\",\n  \"variables\": {\n    \"VAR1\": \"$user->name\",\n    \"VAR2\": \"$user->email\",\n    \"VAR3\": \"$password\"\n  }\n}",
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Content-Type: application/json",
                "authkey:" . env('Msg91_KEY') . ""
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            // echo $response;
        }
    }
    /**
     * Delete a global account
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy($user)
    {
        $user->delete();
    }

    /**
     * Update Global Account Info
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function update($request, $user)
    {
        $user = $this->setUser($request, $user);
        if ($user->is_quest_employee == User::IS_QUEST_EMPLOYEE) {
            $user->givePermissionTo('mqops.full.access');
        } else {
            $user->revokePermissionTo('mqops.full.access');
        }
        $user->update();

        $user->syncRoles($request['account_type']);
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
        $user->email = $request['email'];
        $user->mobile = $request['mobile'];
        if ($request['password'] != "") {
            $user->password = Hash::make($request['password']);
        }
        $user->type = User::TYPE_ADMIN;
        $user->created_platform = User::CREATED_PLATFORM_ADMIN;
        $user->is_quest_employee = $request['is_quest_employee'];
        $user->organisation_id = isset($request['organisation_id']) ? ($request['organisation_id'] ?: null) : null;
        $user->centre_id = isset($request['centre_id']) ? ($request['centre_id'] ?: null) : null;
        $user->program_id = isset($request['program_id']) ? ($request['program_id'] ?: null) : null;
        $user->project_id = isset($request['project_id']) ? ($request['project_id'] ?: null) : null;
        $user->tenant_id = getTenant();
        return $user;
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
     * @param mixed $user
     *
     * @return [type]
     */
    public function updateMqOps($user)
    {
        if ($user->hasDirectPermission('mqops.access')) {
            $user->revokePermissionTo('mqops.access');
        } else {
            $user->givePermissionTo('mqops.access');
        }
        $user->update();
        return $user;
    }
    /**
     * List all role
     *
     * @return [type]
     */
    public function getRoles()
    {
        $roles = QueryBuilder::for(Role::class)
            ->with(['permissions' => function ($q) {
                $q->where('type', Permission::PERMISSION_TYPE_ONE);
            }])
            ->allowedFilters(['name'])
            ->where('type', Role::TYPE_STATUS)
            ->where('status', Role::ACTIVE_STATUS)
            ->get();
        return $roles;
    }
}
