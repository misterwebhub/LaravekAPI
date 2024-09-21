<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Repositories\v1\ApprovalRepository;
use Illuminate\Http\Request;
use App\Http\Resources\v1\ApprovalResource;
use App\Http\Requests\v1\ApprovalsRequest;
use App\Models\Approval;

class ApprovalController extends Controller
{
    private $approvalRepository;
    /**
     * @param StudentMergeRepository $centreMergeRepository
     */
    public function __construct(ApprovalRepository $approvalRepository)
    {
        $this->approvalRepository = $approvalRepository;
        $this->middleware('permission:activity.can.approve');
    }
    /**
     * @return [json]
     */
    public function listPendingApprovals(Request $request)
    {
        $data = $this->approvalRepository->listPendingApprovals($request->all());
        return ApprovalResource::collection($data['pendingApprovals'])
        ->additional(['total_count' => $data['totalCount']]);
    }

    /**
     * @return [json]
     */
    public function approvePendingApprovals(ApprovalsRequest $request, Approval $approval)
    {
        $data = $this->approvalRepository->approvePendingApprovals($request, $approval);
        return response([
            'message' => $data['message']
        ], 200);
    }
}
