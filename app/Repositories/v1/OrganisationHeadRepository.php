<?php

namespace App\Repositories\v1;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\QueryBuilder;
use App\Services\Filter\OrganizationHeadCustomFilter;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Class OrganisationHeadRepository
 * @package App\Repositories
 */
class OrganisationHeadRepository
{
    /**
     * List all organisation heads
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request, $organisation)
    {
        $organisationHead = QueryBuilder::for(User::class)
            ->with('roles')
            ->role('organisation-head')
            ->allowedSorts(['name', 'email', 'mobile', 'status'])
            ->where('tenant_id', getTenant())
            ->where('type', User::TYPE_ADMIN)
            ->where('organisation_id', $organisation->id)
            ->allowedFilters([
                AllowedFilter::custom('search_value', new OrganizationHeadCustomFilter())->ignore(null),
            ])
            ->latest()->paginate($request['limit'] ?? null);
        return $organisationHead;
    }

    /**
     * Create a new Organisation head
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request, $organisation)
    {
        $organisationHead = new User();
        $organisationHead = $this->setOrganisationHead($request, $organisationHead);
        $organisationHead->organisation_id = $organisation->id;
        $organisationHead->save();
        $organisationHead->syncRoles("organisation-head");
        return $organisationHead;
    }

    /**
     * @param mixed $organisation head
     *
     * @return [type]
     */
    public function destroy(User $organisationhead)
    {
        $organisationhead->delete();
    }

    /**
     * @param mixed $request
     * @param mixed $organisation head
     *
     * @return [type]
     */
    public function update($request, $organisationHead)
    {
        $organisationHead = $this->setOrganisationHead($request, $organisationHead);
        $organisationHead->update();

        return $organisationHead;
    }

    /**
     * @param mixed $request
     * @param mixed $organisation head
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
     * Set Organisation head Data
     * @param mixed $request
     * @param mixed $organisation head
     *
     * @return [collection]
     */
    private function setOrganisationHead($request, $organisationHead)
    {
        $organisationHead->name = $request['name'];
        $organisationHead->email = $request['email'];
        $organisationHead->type = User::TYPE_ADMIN;
        $organisationHead->mobile = isset($request['mobile']) ? ($request['mobile'] ?: null) : null;
        if ($request['password'] != "") {
            $organisationHead->password = Hash::make($request['password']);
        }
        $organisationHead->tenant_id = getTenant();
        return $organisationHead;
    }
}
