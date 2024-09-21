<?php

namespace App\Repositories\v1;

use App\Models\NotificationContent;
use Spatie\QueryBuilder\QueryBuilder;
use App\Services\Filter\NotificationContentCustomFilter;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Class NotificationContentRepository
 * @package App\Repositories
 */
class NotificationContentRepository
{
    /**
     * List all notification contents
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request)
    {
        $notificationContents = QueryBuilder::for(NotificationContent::class)
            ->allowedFilters([
                'content',
                AllowedFilter::custom('search_value', new NotificationContentCustomFilter()),
            ])
            ->allowedSorts(
                ['comment', 'content', 'key']
            )
            ->where('tenant_id', getTenant())
            ->latest()
            ->paginate($request['limit'] ?? null);
        return $notificationContents;
    }

    /**
     * @param mixed $request
     * @param mixed $notification content
     *
     * @return [type]
     */
    public function updateContent($request, $notificationContent)
    {
        $notificationContent->content = $request['content'];
        $notificationContent->update();
        return $notificationContent;
    }
}
