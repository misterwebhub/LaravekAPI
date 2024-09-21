<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\StudentExportRequest;
use App\Http\Requests\v1\PhaseStudentExportRequest;
use App\Http\Requests\v1\StudentImportRequest;
use App\Http\Requests\v1\StudentBatchRequest;
use App\Repositories\v1\CentreAdminRepository;
use Illuminate\Http\Request;
use App\Models\Batch;
use App\Models\User;
use App\Http\Resources\v1\StudentBatchResource;
use App\Http\Resources\v1\TradeResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\v1\StudentResource;

class CentreAdminController extends Controller
{
    private $centreAdminRepository;

    /**
     * @param CentreAdminRepository $centreAdminRepository
     */
    public function __construct(CentreAdminRepository $centreAdminRepository)
    {
        $this->centreAdminRepository = $centreAdminRepository;
        $this->middleware('permission:learner.view', ['only' => ['exportStudent', 'listStudents']]);
        $this->middleware('permission:learner.create', ['only' => ['importStudent']]);
        $this->middleware('permission:learner.update', ['only' => ['importStudent', 'updateStudent']]);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function exportStudent(StudentExportRequest $request)
    {
        $filePath = $this->centreAdminRepository->exportStudent($request->all(), $request->user());
        return response([
            'file_path' => $filePath,
        ], 200);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function exportPhaseStudent(PhaseStudentExportRequest $request)
    {
        $filePath = $this->centreAdminRepository->exportPhaseStudent($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);
    }
    
    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function importStudent(StudentImportRequest $request)
    {
        $this->authorize('update', Batch::find($request->batch_id));
        $userId = $request->user()->id;
        $filePath = $this->centreAdminRepository->importStudent($request->all(), $userId);
        return response([
            'data' => $filePath,
        ], 200);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function listStudents(Batch $batch, Request $request)
    {
        $this->authorize('view', $batch);
        $students = $this->centreAdminRepository->listStudents($batch->id, $request->all());
        return StudentBatchResource::collection($students);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function getTrades()
    {
        $centerType = auth::user()->centre->centreType->type;
        $trades = $this->centreAdminRepository->getTrades($centerType);
        return TradeResource::collection($trades);
    }

    /**
     * @param StudentBatchRequest $request
     *
     * @return [type]
     */
    public function updateStudent(StudentBatchRequest $request)
    {
        $this->authorize('update', User::find($request->id));
        $student = $this->centreAdminRepository->updateStudent($request->all());
        return (new StudentResource($student))->additional(['message' => trans('admin.student_updated')]);
    }
}
