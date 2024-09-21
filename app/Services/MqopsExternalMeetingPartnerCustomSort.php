<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\MqopsPartnerType;
use Spatie\QueryBuilder\Sorts\Sort;

class MqopsExternalMeetingPartnerCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            MqopsPartnerType::select('name as typeName')
                ->whereColumn('mqops_external_meetings.mqops_partner_type_id', 'mqops_partner_types.id'),
            $direction
        );
    }
}
