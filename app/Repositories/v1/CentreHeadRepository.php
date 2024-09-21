<?php

namespace App\Repositories\v1;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Services\Filter\CentreHeadCustomFilter  ;

/**
 * Class CentreHeadRepository
 * @package App\Repositories
 */
class CentreHeadRepository
{
    /**
     * List all centre heads
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request, $centre)
    {
        $centreHead = QueryBuilder::for(User::class)
            ->with('roles')
            ->role('centre-head')
            ->allowedSorts(['name', 'email', 'mobile', 'status'])
            ->allowedFilters(
                [AllowedFilter::custom('search_value', new CentreHeadCustomFilter()) ]
            )
            ->where('tenant_id', getTenant())
            ->where('type', User::TYPE_ADMIN)
            ->where('centre_id', $centre->id)
            ->latest();
        $totalCount = $centreHead->get()->count();
        if (isset($request['limit'])) {
            $centreHead = $centreHead->paginate($request['limit'] ?? null);
        } else {
            $centreHead = $centreHead->get();
        }
        return ['centreHead' => $centreHead, 'total_count' => $totalCount];
    }

    /**
     * Create a new Centre head
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request, $centre)
    {
        $centreHead = new User();
        $centreHead = $this->setCentreHead($request, $centreHead);
        $centreHead->centre_id = $centre->id;
        $centreHead->organisation_id = $centre->organisation_id;
        $centreHead->created_platform = User::CREATED_PLATFORM_CENTRE_ADMIN;
        $centreHead->save();
        $centreHead->syncRoles("centre-head");
        return $centreHead;
    }

    /**
     * @param mixed $centre head
     *
     * @return [type]
     */
    public function destroy(User $centrehead)
    {
        $centrehead->delete();
    }

    /**
     * @param mixed $request
     * @param mixed $centre head
     *
     * @return [type]
     */
    public function update($request, $centreHead)
    {
        $centreHead = $this->setCentreHead($request, $centreHead);
        $centreHead->update();

        return $centreHead;
    }

    /**
     * @param mixed $request
     * @param mixed $centre head
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
     * Set Centre head Data
     * @param mixed $request
     * @param mixed $centre head
     *
     * @return [collection]
     */
    private function setCentreHead($request, $centreHead)
    {
        $centreHead->name = $request['name'];
        $centreHead->email = $request['email'];
        $centreHead->type = User::TYPE_ADMIN;
        $centreHead->mobile = isset($request['mobile']) ? ($request['mobile'] ?: null) : null;
        if ($request['password'] != "") {
            $centreHead->password = Hash::make($request['password']);
        }
        $centreHead->tenant_id = getTenant();
        return $centreHead;
    }
}
