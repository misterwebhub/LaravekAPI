<?php

namespace App\Repositories\v1;

use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Approval;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\ApprovalUserCustomSort;
use App\Services\ApprovalUserRoleCustomSort;

/**
 * Class ApprovalRepository
 * @package App\Repositories
 */
class ApprovalRepository
{
    /**
     * List all pending approvals
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function listPendingApprovals($request)
    {
        $pendingApprovals = QueryBuilder::for(Approval::class)
            ->with('referenceUser')
            ->where('status', 0);
        $totalCount = $pendingApprovals->get()->count();
        $pendingApprovals = $pendingApprovals
            ->allowedIncludes('referenceUser', 'roles')
            ->allowedFilters(
                'referenceUser.name',
                'title',
                AllowedFilter::exact('referenceUser.roles.name')->ignore(null)
            )
            ->allowedSorts(
                [
                    'title',
                    AllowedSort::custom('user_name', new ApprovalUserCustomSort()),
                ]
            )
            ->latest()
            ->paginate($request['limit'] ?? null);
        return array(
            'totalCount' => $totalCount,
            'pendingApprovals' => $pendingApprovals
        );
    }

    /**
     * Approve all pending approvals
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function approvePendingApprovals($request, $approval)
    {
        $user = $request->user();
        try {
            DB::beginTransaction();
            if ($request->status == 1) { // to approve
                $approval->status = Approval::TYPE_APPROVED;
                $approval->update();
                if ($approval->type == Approval::TYPE_SINGLE) {
                    $model = $approval->model;
                    $NamespacedModel = '\\App\Models\\' . $model;
                    $action = $NamespacedModel::where('id', $approval->reference_id)
                    ->update(
                        [
                            "is_approved" => Approval::TYPE_APPROVED,
                            "approved_by" => $user->id
                        ]
                    );
                } else {
                    $model = $approval->model;
                    $NamespacedModel = '\\App\Models\\' . $model;
                    $action = $NamespacedModel::
                    where('approval_group', $approval->reference_id)
                    ->update(
                        [
                            "is_approved" => Approval::TYPE_APPROVED,
                            "approved_by" => $user->id
                        ]
                    );
                }
                DB::commit();
                return array(
                    'data' => $approval,
                    'message' => trans('approvals.approved')
                );
            } elseif ($request->status == 0) { // to delete
                $approval->status = Approval::TYPE_REJECTED;
                $approval->update();
                if ($approval->type == Approval::TYPE_SINGLE) {
                    $model = $approval->model;
                    $NamespacedModel = '\\App\Models\\' . $model;
                    $action = $NamespacedModel::where('id', $approval->reference_id)
                    ->update(
                        [
                            "is_approved" => Approval::TYPE_REJECTED,
                            "approved_by" => $user->id
                        ]
                    );
                    Approval::where('id', $approval->id)->delete();
                    $NamespacedModel::where('id', $approval->reference_id)->delete();
                } else {
                    $model = $approval->model;
                    $NamespacedModel = '\\App\Models\\' . $model;
                    $action = $NamespacedModel::
                    where('approval_group', $approval->reference_id)
                    ->update(
                        [
                            "is_approved" => Approval::TYPE_REJECTED,
                            "approved_by" => $user->id
                        ]
                    );
                    Approval::where('id', $approval->id)->delete();
                    $NamespacedModel::where('approval_group', $approval->reference_id)->get();
                }
                DB::commit();
                return array(
                    'data' => $approval,
                    'message' => trans('approvals.rejected')
                );
            } else {
                return array(
                    'message' => trans('admin.data_invalid')
                );
            }
        } catch (\Exception $e) {
            DB::rollback();
            return array(
                'message' => trans('admin.operation failed')
            );
        }
    }
}
