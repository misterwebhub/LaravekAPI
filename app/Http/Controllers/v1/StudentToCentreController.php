<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\StudentToCentreImportRequest;
use App\Repositories\v1\StudentToCentreRepository;
use Illuminate\Http\Request;
use App\Models\Centre;

class StudentToCentreController extends Controller
{
    private $studentToCentreRepository;

    /**
     * @param StudentToCentreRepository $studentToCentreRepository
     */
    public function __construct(StudentToCentreRepository $studentToCentreRepository)
    {
        $this->studentToCentreRepository = $studentToCentreRepository;
        $this->middleware(
            'permission:learner.create|learner.update',
            ['only' => ['importStudentToCentre']]
        );
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function importStudentToCentre(StudentToCentreImportRequest $request)
    {
        $this->authorize('update', Centre::find($request->centre_id));
        $filePath = $this->studentToCentreRepository->importStudentToCentre($request->all());
        return response([
            'data' => $filePath,
        ], 200);
    }
}
