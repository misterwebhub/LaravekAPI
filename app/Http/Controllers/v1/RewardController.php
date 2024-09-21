<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\BadgeResource;
use App\Http\Resources\v1\PointResource;
use App\Models\Point;
use App\Repositories\v1\RewardRepository;
use Illuminate\Http\Request;
use App\Http\Requests\v1\BadgeRequest;

class RewardController extends Controller
{
    private $rewardRepository;

    /**
     * @param RewardRepository $rewardRepository
     */
    public function __construct(RewardRepository $rewardRepository)
    {

        $this->rewardRepository = $rewardRepository;
        $this->middleware('permission:reward.update', ['only' =>
        ['updatePoints', 'updateBadges']]);
        $this->middleware('permission:reward.view', ['only' =>
        ['listPoint', 'getBadges', 'getBadgeTypes']]);
    }

    /**
     *
     * @return [json]
     */
    public function listPoint()
    {
        return PointResource::collection(Point::get());
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function updatePoints(Request $request)
    {
        $this->rewardRepository->updatePoints($request->all());

        return PointResource::collection(Point::get())->additional(['message' => trans('admin.points_updated')]);
    }

    /**
     *
     * @return [json]
     */
    public function getBadgeTypes()
    {
        return $this->rewardRepository->getBadgeTypes();
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function getBadges(Request $request)
    {
        $badges = $this->rewardRepository->getBadges($request->all());
        $badges['badges'] = BadgeResource::collection($badges['badges']);
        return $badges;
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function updateBadges(BadgeRequest $request)
    {
        $badges = $this->rewardRepository->updateBadges($request->all());
        $badges['badges'] = BadgeResource::collection($badges['badges']);
        $badges['message'] = trans('admin.badges_updated');
        return $badges;
    }
}
