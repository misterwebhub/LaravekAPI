<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\v1\MqopsTeamActivityRequest;
use App\Http\Requests\v1\MqopsActivityRequest;
use App\Http\Resources\v1\MqopsActivityResource;
use App\Http\Resources\v1\CentreTypeResource;
use App\Http\Resources\v1\MqopsActivityMediumResource;
use App\Http\Resources\v1\MqopsActivityTypeResource;
use App\Http\Resources\v1\MqopsDesignationResource;
use App\Models\MqopsLeaderDesignation;
use App\Models\MqopsActivity;
use App\Models\MqopsActivityType;
use App\Models\CentreType;
use App\Models\MqopsActivityMedium;
use App\Repositories\v1\MqopsActivityRepository;

class MqopsActivityController extends Controller
{
    private $mqopsActivityRepository;
    /**
     * @param MqopsActivityRepository $mqopsActivityRepository
     */
    public function __construct(MqopsActivityRepository $mqopsActivityRepository)
    {
        $this->mqopsActivityRepository = $mqopsActivityRepository;
        $this->middleware(
            'permission:mqops.access',
            ['only' => ['index', 'store', 'show', 'update', 'destroy']]
        );
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $activity = $this->mqopsActivityRepository->index($request->all());
        return MqopsActivityResource::collection($activity);
    }

    /**
     * @param MqopsActivityRequest $request
     *
     * @return [type]
     */
    public function store(MqopsActivityRequest $request)
    {
        $total = null;
        $femaleCount = $request->female_participants_count ?? 0;
        $maleCount = $request->male_participants_count ?? 0;
        $otherCount = $request->other_participants_count ?? 0;
        if ($femaleCount || $maleCount || $otherCount) {
            $femaleCount = trim($femaleCount) == "" ? 0 : $femaleCount;
            $maleCount = trim($maleCount) == "" ? 0 : $maleCount;
            $otherCount = trim($otherCount) == "" ? 0 : $otherCount;
            $total = $femaleCount + $maleCount + $otherCount;
        }

        if ($total && $total !=  $request->participants_count) {
            $data['status'] = 0;
            $data['message'] = 'Error in participants count';
            return response()->json($data, 200);
        }
        $user = $request->user();
        $activity = $this->mqopsActivityRepository->store($request->all(), $user);
        return (new MqopsActivityResource($activity))
            ->additional(['message' => trans('admin.mqops_activity_added')]);
    }

    /**
     * @param mixed $activity
     *
     * @return [type]
     */
    public function show(MqopsActivity $activity)
    {
        return new MqopsActivityResource($activity);
    }

    /**
     * @param MqopsActivityRequest $request
     * @param mixed $activity
     *
     * @return [type]
     */
    public function update(MqopsActivityRequest $request, MqopsActivity $activity)
    {
        $total = null;
        $femaleCount = $request->female_participants_count ?? 0;
        $maleCount = $request->male_participants_count ?? 0;
        $otherCount = $request->other_participants_count ?? 0;
        if ($femaleCount || $maleCount || $otherCount) {
            $femaleCount = trim($femaleCount) == "" ? 0 : $femaleCount;
            $maleCount = trim($maleCount) == "" ? 0 : $maleCount;
            $otherCount = trim($otherCount) == "" ? 0 : $otherCount;
            $total = $femaleCount + $maleCount + $otherCount;
        }

        if ($total && $total !=  $request->participants_count) {
            $data['status'] = 0;
            $data['message'] = 'Error in participants count';
            return response()->json($data, 422);
        }

        $user = $request->user();
        $activity = $this->mqopsActivityRepository->update($request->all(), $activity, $user);
        return (new MqopsActivityResource($activity))
            ->additional(['message' => trans('admin.mqops_activity_updated')]);
    }

    /**
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy(MqopsActivity $activity)
    {
        $this->mqopsActivityRepository->destroy($activity);
        return response(['message' => trans('admin.mqops_activity_deleted')]);
    }

    /**
     *
     * @return [json]
     */
    public function getActivityMedium()
    {
        return MqopsActivityMediumResource::collection(MqopsActivityMedium::all());
    }

    /**
     *
     * @return [json]
     */
    public function getActivityType()
    {
        return MqopsActivityTypeResource::collection(MqopsActivityType::where('status', 1)->orderBy("name", "ASC")->get());
    }

    /**
     *
     * @return [json]
     */
    public function getInstitutionType()
    {
        return CentreTypeResource::collection(CentreType::all());
    }

    /**
     *
     * @return [json]
     */
    public function getDesignationType()
    {
        return MqopsDesignationResource::collection(MqopsLeaderDesignation::all());
    }


    public function exportActivity(Request $request)
    {
        $filePath = $this->mqopsActivityRepository->exportActivity($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);

    }
    /**
     *
     * @return [json]
     */
    public function getTeamsActiviy(MqopsTeamActivityRequest $request)
    {
        $data = config("mqops." . $request->type);
        $i = 0;
        $response = [];
        foreach ($data as $dataid) {
            $value = (object)['id' => $i++, 'name' => $dataid];
            array_push($response, $value);
        }
        return response(['data' => $response]);

    }
}
