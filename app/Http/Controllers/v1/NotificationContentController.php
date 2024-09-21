<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\NotificationContentRequest;
use App\Http\Resources\v1\NotificationContentResource;
use App\Models\NotificationContent;
use App\Repositories\v1\NotificationContentRepository;
use Illuminate\Http\Request;

class NotificationContentController extends Controller
{
    private $notificationContentRepository;
    /**
     * @param NotificationContentRepository $notificationContentRepository
     */
    public function __construct(NotificationContentRepository $notificationContentRepository)
    {
        $this->notificationContentRepository = $notificationContentRepository;
        $this->middleware('permission:notification.view', ['only' => ['index']]);
        $this->middleware(
            'permission:notification.update',
            ['only' => ['edit', 'updateContent']]
        );
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function index(Request $request)
    {
        $notificationContents = $this->notificationContentRepository->index($request->all());
        return NotificationContentResource::collection($notificationContents);
    }

    /**
     * @param User $notificationContent
     *
     * @return [type]
     */
    public function edit(NotificationContent $notificationContent)
    {
        return new NotificationContentResource($notificationContent);
    }

    /**
     * @param UpdateContentNotificationContentRequest $request
     * @param Report $notificationContent
     *
     * @return [type]
     */
    public function updateContent(NotificationContentRequest $request, NotificationContent $notificationContent)
    {
        $notificationContent = $this->notificationContentRepository
            ->updateContent($request->all(), $notificationContent);
        return (new NotificationContentResource($notificationContent))
            ->additional(['message' => trans('admin.notification_content_updated')]);
    }
}
