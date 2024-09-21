<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Repositories\v1\StudentMergeRepository;
use App\Http\Requests\v1\StudentMergeRequest;
use App\Models\User;

class StudentMergeController extends Controller
{
    private $studentMergeRepository;
    /**
     * @param StudentMergeRepository $studentMergeRepository
     */
    public function __construct(StudentMergeRepository $studentMergeRepository)
    {
        $this->studentMergeRepository = $studentMergeRepository;
        $this->middleware('permission:learner.merge', ['only' => ['studentMerge']]);
    }
    /**
     * @param StudentMergeRequest $request
     *
     * @return [json]
     */
    public function studentMerge(StudentMergeRequest $request)
    {
        $this->authorize('update', User::find($request->from_student));
        $data = $this->studentMergeRepository->studentMerge($request->all());
        return $data;
    }
}
