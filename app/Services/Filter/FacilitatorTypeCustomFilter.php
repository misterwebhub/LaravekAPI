<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use App\Models\User;

class FacilitatorTypeCustomFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $valueArray = is_array($value) ? $value : [$value];
        $query->where(function ($qry2) use ($valueArray) {
            foreach ($valueArray as $val) {
                if ($val == User::FILTER_TYPE_ONE) {
                    $qry2 = $qry2->orWhere('users.is_super_facilitator', User::ACTIVE_STATUS);
                } elseif ($val == User::FILTER_TYPE_ZERO) {
                    $qry2 = $qry2->orWhere('users.is_master_trainer', User::ACTIVE_STATUS);
                } elseif ($val == User::FILTER_TYPE_THREE) {
                    $qry2->orWhere(function ($qry3) {
                        $qry3->where(function ($qry4) {
                            $qry4 = $qry4->where('users.is_master_trainer', User::INACTIVE_STATUS)
                                ->orWhereNull('users.is_master_trainer');
                        });
                        $qry3->where(function ($qry4) {
                            $qry4 = $qry4->where('users.is_super_facilitator', User::INACTIVE_STATUS)
                                ->orWhereNull('users.is_super_facilitator');
                        });
                    });
                }
            }
        });

        return $query;
    }
}
