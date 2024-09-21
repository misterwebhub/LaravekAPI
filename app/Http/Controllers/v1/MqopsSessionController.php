<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\v1\MqopsTeamActivityRequest;
use App\Http\Requests\v1\MqopsSessionRequest;
use App\Http\Resources\v1\MqopsSessionResource;
use App\Http\Resources\v1\CentreTypeResource;
use App\Http\Resources\v1\ProjectResource;
use App\Http\Resources\v1\MqopsActivityMediumResource;
use App\Http\Resources\v1\MqopsDesignationResource;
use App\Models\MqopsLeaderDesignation;
use App\Models\MqopsSession;
use App\Models\MqopsSessionType;
use App\Models\SessionType;
use App\Models\CentreType;
use App\Models\MqopsActivityMedium;
use App\Repositories\v1\MqopsSessionRepository;

class MqopsSessionController extends Controller
{
    private $mqopsSessionRepository;
    /**
     * @param MqopsSessionRepository $mqopsSessionRepository
     */
    public function __construct(MqopsSessionRepository $mqopsSessionRepository)
    {
        $this->mqopsSessionRepository = $mqopsSessionRepository;
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
        $session = $this->mqopsSessionRepository->index($request->all());
        return MqopsSessionResource::collection($session);
    }

    /**
     * @param MqopsSessionRequest $request
     *
     * @return [type]
     */
    public function store(MqopsSessionRequest $request)
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
        $session = $this->mqopsSessionRepository->store($request->all(), $user);
        return (new MqopsSessionResource($session))
            ->additional(['message' => trans('admin.mqops_session_added')]);
    }

    /**
     * @param MqopsSessionRequest $request
     * @param mixed $session
     *
     * @return [type]
     */
    public function update(MqopsSessionRequest $request, MqopsSession $session)
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
        $session = $this->mqopsSessionRepository->update($request->all(), $session, $user);
        return (new MqopsSessionResource($session))
            ->additional(['message' => trans('admin.mqops_session_updated')]);
    }

    /**
     * @param mixed $session
     *
     * @return [type]
     */
    public function show(MqopsSession $session)
    {
        return new MqopsSessionResource($session);
    }

    /**
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy(MqopsSession $session)
    {
        $this->mqopsSessionRepository->destroy($session);
        return response(['message' => trans('admin.mqops_session_deleted')]);
    }


    /**
     *
     * @return [json]
     */
    public function getSessionType(Request $request)
    {
        $user = $request->user();
        if ($user->is_quest_employee == 1){
            return response(['data' => SessionType::all()]);
        }
        else{
            return response(['data' => SessionType::where('type', 1)->get()]);
        }
    }

    /**
     *
     * @return [json]
     */
    public function getProjectList(Request $request)
    {
        $projects = $this->mqopsSessionRepository->projectList($request->all());
        return ProjectResource::collection($projects);
    }

    /**
     *
     * @return [json]
     */
    public function getActivityMedium()
    {
        return MqopsActivityMediumResource::collection(MqopsActivityMedium::all());
    }

    public function exportSession(Request $request)
    {
        $filePath = $this->mqopsSessionRepository->exportSession($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);

    }
}
