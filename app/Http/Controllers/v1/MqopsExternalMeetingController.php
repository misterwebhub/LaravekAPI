<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\MqopsExternalMeetingRequest;
use App\Http\Resources\v1\MqopsExternalMeetingResource;
use App\Models\MqopsExternalMeeting;
use App\Repositories\v1\MqopsExternalMeetingRepository;
use Illuminate\Http\Request;
use Auth;

class MqopsExternalMeetingController extends Controller
{
    private $mqopsExternalMeetingRepository;
    /**
     * @param MqopsExternalMeetingRepository $mqopsExternalMeetingRepository
     */
    public function __construct(mqopsExternalMeetingRepository $mqopsExternalMeetingRepository)
    {
        $this->mqopsExternalMeetingRepository = $mqopsExternalMeetingRepository;
        $this->middleware('permission:mqops.full.access', ['only' => ['index', 'store', 'update', 'destroy', 'edit']]);
        // $this->middleware(
        //     'role:super-admin',
        //     ['only' => ['exportExternalMeeting']]
        // );
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $mqopsExternalMeeting = $this->mqopsExternalMeetingRepository->index($request->all());
        return MqopsExternalMeetingResource::collection($mqopsExternalMeeting);
    }

    /**
     * @param MqopsExternalMeetingRequest $request
     *
     * @return [type]
     */
    public function store(MqopsExternalMeetingRequest $request)
    {
        $user = $request->user();
        $mqopsExternalMeeting = $this->mqopsExternalMeetingRepository->store($request->all(), $user);
        return (new mqopsExternalMeetingResource($mqopsExternalMeeting))
            ->additional(['message' => trans('admin.mqops_external_meeting_added')]);
    }

    /**
     * @param MqopsExternalMeetingRequest $request
     * @param mixed $mqopsExternalMeeting
     *
     * @return [type]
     */
    public function update(MqopsExternalMeetingRequest $request, mqopsExternalMeeting $mqopsExternalMeeting)
    {
        $user = $request->user();
        $mqopsExternalMeeting = $this->mqopsExternalMeetingRepository->update(
            $request->all(),
            $mqopsExternalMeeting,
            $user
        );
        return (new MqopsExternalMeetingResource($mqopsExternalMeeting))
            ->additional(['message' => trans('admin.mqops_external_meeting_updated')]);
    }

    /**
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy(MqopsExternalMeeting $mqopsExternalMeeting)
    {
        $this->mqopsExternalMeetingRepository->destroy($mqopsExternalMeeting);
        return response(['message' => trans('admin.mqops_external_meeting_deleted')]);
    }

    /**
     * @param MqopsExternalMeeting $mqopsExternalMeeting
     *
     * @return [json]
     */
    public function edit(MqopsExternalMeeting $mqopsExternalMeeting)
    {
        return new MqopsExternalMeetingResource($mqopsExternalMeeting);
    }

    public function exportExternalMeeting(Request $request)
    {
        $filePath = $this->mqopsExternalMeetingRepository->exportExternalMeeting($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);

    }
}
