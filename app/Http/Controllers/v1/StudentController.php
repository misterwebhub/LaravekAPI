<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\StudentRequest;
use App\Http\Requests\v1\UpdateStatusUserRequest;
use App\Http\Resources\v1\StudentResource;
use App\Models\Centre;
use App\Models\Project;
use App\Models\User;
use App\Repositories\v1\StudentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    private $studentRepository;
    /**
     * @param studentRepository $studentRepository
     */
    public function __construct(StudentRepository $studentRepository)
    {
        $this->studentRepository = $studentRepository;
        $this->middleware('permission:learner.view', ['only' => ['show', 'index', 'exportStudent']]);
        $this->middleware('permission:learner.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:learner.update', ['only' => ['update', 'updateStatus']]);
        $this->middleware('permission:learner.destroy', ['only' => ['destroy']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $students = $this->studentRepository->index($request->all(), $request->user());
        return StudentResource::collection($students['students'])
            ->additional(['total_without_filter' => $students['total_count']]);
    }

    /**
     * @param StudentRequest $request
     *
     * @return [type]
     */
    public function store(StudentRequest $request)
    {
        $this->authorize('create', Centre::find($request->centre_id));
        $user = $request->user();
        $student = $this->studentRepository->store($request->all(), $user);
        return (new StudentResource($student))
            ->additional(['message' => trans('admin.student_added')]);
    }

    /**
     * @param studentRequest $request
     * @param mixed $id
     *
     * @return [type]
     */
    public function update(StudentRequest $request, User $student)
    {
        $this->authorize('update', $student);

        $students = $this->studentRepository->update($request->all(), $student);
        return (new StudentResource($students))
            ->additional(['message' => trans('admin.student_updated')]);
    }

    /**
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy(User $student)
    {
        $this->authorize('delete', $student);
        $this->studentRepository->destroy($student);
        return response(['message' => trans('admin.student_deleted')]);
    }

    /**
     * @param user $student
     *
     * @return [json]
     */
    public function show(User $student)
    {
        $this->authorize('view', $student);
        return new StudentResource($student);
    }

    /**
     * @param UpdateStatusUserRequest $request
     * @param Lesson $lesson
     *
     * @return [type]
     */
    public function updateStatus(UpdateStatusUserRequest $request, User $student)
    {
        $this->authorize('update', $student);
        $student = $this->studentRepository->updateStatus($request->all(), $student);
        return (new StudentResource($student))
            ->additional(['message' => trans('admin.student_status_updated')]);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function getOrganisations(Project $project)
    {
        $this->authorize('view', $project);
        $organisations = $this->studentRepository->getOrganisations($project, Auth::user());
        return response([
            'organisations' => $organisations
        ], 200);
    }

    /**
     * @return [type]
     */
    public function exportStudent(Request $request)
    {
        $filePath = $this->studentRepository->exportStudent($request->all(), $request->user());
        return response([
            'file_path' => $filePath,
        ], 200);
    }
    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function studentCount(Request $request)
    {
        $students = $this->studentRepository->studentCount($request->all(), $request->user());

        return response()->json([
            'data' =>   $students

        ], 200);
    }
    /**
     * @param UpdateStatusUserRequest $request
     * @param User $student
     *
     * @return [json]
     */
    public function updateRegistrationStatus(UpdateStatusUserRequest $request, User $student)
    {
        $this->authorize('update', $student);
        $student = $this->studentRepository->updateRegistrationStatus($request->all(), $student);
        return (new StudentResource($student))
            ->additional(['message' => trans('admin.student_registarion_status_updated')]);
    }
}
