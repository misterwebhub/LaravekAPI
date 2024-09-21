<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\AccountRequest;
use App\Http\Requests\v1\UpdateStatusUserRequest;
use App\Http\Resources\v1\AccountResource;
use App\Http\Resources\v1\AccountTypeResource;
use App\Models\User;
use App\Repositories\v1\AccountRepository;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    private $accountRepository;
    /**
     * @param AccountRepository $accountRepository
     */
    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
        $this->middleware('permission:account.view', ['only' => ['index']]);
        $this->middleware('permission:account.create', ['only' => ['store']]);
        $this->middleware('permission:account.update', ['only' => ['edit', 'update', 'updateStatus', 'updateMqOps']]);
        $this->middleware('permission:account.destroy', ['only' => ['destroy']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $accounts = $this->accountRepository->index($request->all());
        return AccountResource::collection($accounts)
            ->additional(['total_without_filter' => User::where('type', User::TYPE_ADMIN)->count()]);
    }

    /**
     * @param AccountRequest $request
     *
     * @return [type]
     */
    public function store(AccountRequest $request)
    {

        $account = $this->accountRepository->store($request->all());
        return (new AccountResource($account))
            ->additional(['message' => trans('admin.account_added')]);
    }

    /**
     * @param AccountRequest $request
     * @param mixed $account
     *
     * @return [type]
     */
    public function update(AccountRequest $request, User $account)
    {
        $account = $this->accountRepository->update($request->all(), $account);
        return (new AccountResource($account))
            ->additional(['message' => trans('admin.account_updated')]);
    }

    /**
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy(User $account)
    {
        $this->accountRepository->destroy($account);
        return response(['message' => trans('admin.account_deleted')]);
    }

    /**
     * @param user $account
     *
     * @return [json]
     */
    public function edit(User $account)
    {
        return new AccountResource($account);
    }

    /**
     * @param UpdateStatusUserRequest $request
     * @param User $user
     *
     * @return [type]
     */
    public function updateStatus(UpdateStatusUserRequest $request, User $account)
    {
        $account = $this->accountRepository->updateStatus($request->all(), $account);
        return (new AccountResource($account))
            ->additional(['message' => trans('admin.account_status_change')]);
    }

    /**
     * @param User $user
     *
     * @return [type]
     */
    public function updateMqOps(User $account)
    {
        $account = $this->accountRepository->updateMqOps($account);
        return (new AccountResource($account))
            ->additional(['message' => trans('admin.account_mqops_change')]);
    }

    /**
     *
     * @return [json]
     */
    public function getAccountType()
    {
        $roles = $this->accountRepository->getRoles();
        return AccountTypeResource::collection($roles);
    }
}
