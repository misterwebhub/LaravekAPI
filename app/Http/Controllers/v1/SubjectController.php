<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\SubjectRequest;
use App\Http\Requests\v1\SubjectOrderRequest;
use App\Http\Resources\v1\SubjectResource;
use App\Models\Subject;
use App\Repositories\v1\SubjectRepository;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class SubjectController extends Controller
{
    private $subjectRepository;

    /**
     * @param SubjectRepository $subjectRepository
     */
    public function __construct(SubjectRepository $subjectRepository)
    {
        $this->subjectRepository = $subjectRepository;
        $this->middleware('permission:subject.view', ['only' => ['index']]);
        $this->middleware('permission:subject.create', ['only' => ['store']]);
        $this->middleware('permission:subject.update', ['only' =>
        ['show', 'update', 'updateStatus', 'arrangeSubjectOrder', 'subjectOrder']]);
        $this->middleware('permission:subject.destroy', ['only' => ['destroy']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $subjects = $this->subjectRepository->index($request->all());
        return SubjectResource::collection($subjects);
    }

    /**
     * @param SubjectRequest $request
     *
     * @return [json]
     */
    public function store(SubjectRequest $request)
    {
        $subject = $this->subjectRepository->store($request->all());
        return (new SubjectResource($subject))
            ->additional(['message' => trans('admin.subject_added')]);
    }

    /**
     * @param Subject $subject
     *
     * @return [json]
     */
    public function show(Subject $subject)
    {
        return new SubjectResource($subject);
    }

    /**
     * @param SubjectRequest $request
     * @param Subject $subject
     *
     * @return [json]
     */
    public function update(SubjectRequest $request, Subject $subject)
    {
        $subject = $this->subjectRepository->update($request->all(), $subject);
        return (new SubjectResource($subject))
            ->additional(['message' => trans('admin.subject_updated')]);
    }

    /**
     * @param Subject $subject
     *
     * @return [json]
     */
    public function destroy(Subject $subject)
    {
        $this->subjectRepository->destroy($subject);
        return response(['message' => trans('admin.subject_deleted')]);
    }

    /**
     * @param Request $request
     * @param Subject $subject
     *
     * @return [json]
     */
    public function updateStatus(Request $request, Subject $subject)
    {
        $subject = $this->subjectRepository->updateStatus($request->all(), $subject);
        return (new SubjectResource($subject))
            ->additional(['message' => trans('admin.subject_status_updated')]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function arrangeSubjectOrder(SubjectOrderRequest $request)
    {
        $this->subjectRepository->arrangeSubjectOrder($request->all());
        return SubjectResource::collection(Subject::orderByRaw('ISNULL(`order`), `order` ASC')->get())
            ->additional(['message' => trans('admin.subject_order_updated')]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function subjectOrder(Request $request)
    {
        $subjects = QueryBuilder::for(Subject::class)->orderByRaw('ISNULL(`order`), `order` ASC')->get();
        return SubjectResource::collection($subjects);
    }
}
