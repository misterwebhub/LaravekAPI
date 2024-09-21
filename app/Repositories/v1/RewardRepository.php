<?php

namespace App\Repositories\v1;

use App\Models\Badge;
use App\Models\Point;

/**
 * [Description CourseRepository]
 */
class RewardRepository
{

    /**
     * Update reward point
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function updatePoints($request)
    {
        foreach ($request['points'] as $points) {
            $point = Point::find($points['id']);
            $point->point = $points['point'];
            $point->update();
        }
    }

    /**
     * Get badge types from config file
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function getBadgeTypes()
    {
        if (config()->has("staticcontent.badges")) {
            $i = 0;
            $data = [];
            foreach (config('staticcontent.badges') as $key => $value) {
                $data[$i]['id'] = $key;
                $data[$i]['name'] = $value;
                $i++;
            }
            return $data;
        }
    }
    /**
     * Get badges
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function getBadges($request)
    {
        $badgeType = $request['badge_type'];
        if ($badgeType == "course_badges") {
            $data["start_limit"] = 1;
            $badges = Badge::where("bg_type", Badge::ACTIVITY_BADGE_TYPE)
                ->where("bg_category", Badge::COURSE_BADGE);
        } elseif ($badgeType == "community_badges") {
            $data["start_limit"] = 4;
            $badges = Badge::where("bg_type", Badge::ACTIVITY_BADGE_TYPE)
                ->where("bg_category", Badge::COMMUNITY_BADGE);
        } elseif ($badgeType == "resource_badges") {
            $data["start_limit"] = 7;
            $badges = Badge::where("bg_type", Badge::ACTIVITY_BADGE_TYPE)
                ->where("bg_category", Badge::RESOURCE_BADGE);
        } else {
            $data["start_limit"] = 1;
            $badges = Badge::where("bg_type", Badge::PERFORMANCE_BADGE_TYPE);
        }
        $badges = $badges->orderBy("order", "ASC")->get();
        $data["badges"] = $badges;
        return $data;
    }

    /**
     * Update badges
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function updateBadges($request)
    {
        foreach ($request['badges'] as $badges) {
            $badge = Badge::find($badges['id']);
            if ($badges['point'] != null) {
                $badge->point = $badges['point'];
            } else {
                $badge->point = 0;
            }
            $badge->name = $badges['name'];
            $badge->update();
        }
        return $this->getBadges($request);
    }
}
