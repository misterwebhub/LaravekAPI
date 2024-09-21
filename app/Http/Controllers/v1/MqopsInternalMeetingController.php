<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\v1\MqopsInternalMeetingRequest;
use App\Http\Resources\v1\MqopsInternalMeetingResource;
use App\Models\MqopsInternalMeeting;
use App\Repositories\v1\MqopsInternalMeetingRepository;

class MqopsInternalMeetingController extends Controller
{
    private $mqopsInternalMeetingRepository;

    /**
     * @param MqopsInternalMeetingRepository $mqopsInternalMeetingRepository
     */
    public function __construct(MqopsInternalMeetingRepository $mqopsInternalMeetingRepository)
    {
        $this->mqopsInternalMeetingRepository = $mqopsInternalMeetingRepository;
        $this->middleware('permission:mqops.full.access', ['only' => ['index', 'store', 'update', 'destroy', 'edit']]);
    }
    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $mqopsInternalMeeting = $this->mqopsInternalMeetingRepository->index($request->all());
        return MqopsInternalMeetingResource::collection($mqopsInternalMeeting);
    }

    /**
     * @param InternalMeetingRepository $request
     *
     * @return [type]
     */

    public function store(MqopsInternalMeetingRequest $request)
    {
        $user = $request->user();
        $mqopsInternalMeeting = $this->mqopsInternalMeetingRepository->store($request->all(), $user);
        return (new MqopsInternalMeetingResource($mqopsInternalMeeting))
            ->additional(['message' => trans('admin.mqops_internal_meeting_added')]);
    }


    /**
     * @param MqopsInternalMeetingRequest $request
     * @param MqopsInternalMeeting $mqopsInternalMeeting
     *
     * @return [type]
     */

    public function update(MqopsInternalMeetingRequest $request, MqopsInternalMeeting $mqopsInternalMeeting)
    {
        $user = $request->user();
        $mqopsInternalMeeting = $this->mqopsInternalMeetingRepository
            ->update($request->all(), $mqopsInternalMeeting, $user);
        return (new MqopsInternalMeetingResource($mqopsInternalMeeting))
            ->additional(['message' => trans('admin.mqops_internal_meeting_updated')]);
    }
    /**
     * @param MqopsInternalMeetingRequest $request
     * @param MqopsInternalMeeting $mqopsInternalMeeting
     *
     * @return [type]
     */
    public function destroy(MqopsInternalMeeting $mqopsInternalMeeting)
    {
        $this->mqopsInternalMeetingRepository->destroy($mqopsInternalMeeting);
        return response(['message' => trans('admin.mqops_internal_meeting_deleted')], 200);
    }
    /**
     * @param MqopsInternalnalMeeting $mqopsInternalMeeting
     *
     * @return [json]
     */
    public function edit(MqopsInternalMeeting $mqopsInternalMeeting)
    {
        return new MqopsInternalMeetingResource($mqopsInternalMeeting);
    }

    public function exportInternalMeeting(Request $request)
    {
        $filePath = $this->mqopsInternalMeetingRepository->exportInternalMeeting($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);

    }
}
