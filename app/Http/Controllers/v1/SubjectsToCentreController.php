<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\SubjectsToCentreResource;
use App\Models\Centre;
use App\Models\Subject;

use App\Repositories\v1\SubjectsToCentreRepository;
use Illuminate\Http\Request;

class SubjectsToCentreController extends Controller
{
    private $subjectsToCentreRepository;

    /**
     * @param SubjectsToCentreRepository $subjectsToCentreRepository
     */
    public function __construct(SubjectsToCentreRepository $subjectsToCentreRepository)
    {
        $this->subjectsToCentreRepository = $subjectsToCentreRepository;
        $this->middleware('permission:centre.view', ['only' =>
        ['index', 'listSubject']]);
        $this->middleware('permission:centre.update', ['only' =>
        ['assignSubject', 'orderSubject', 'removeSubject']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request, Centre $centre)
    {
        $this->authorize('view', $centre);
        $subjects = $this->subjectsToCentreRepository->index($request->all(), $centre);

        return SubjectsToCentreResource::collection($subjects['subjects'])
            ->additional(['total_without_filter' => $subjects['total_count']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function listSubject(Centre $centre)
    {
        $this->authorize('view', $centre);
        return SubjectsToCentreResource::collection($centre->subjects()->distinct('name')
            ->where('status', Subject::ACTIVE)
            ->orderByRaw('ISNULL(pivot_order), pivot_order ASC')->get());
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function assignSubject(Request $request, Centre $centre)
    {
        $this->authorize('update', $centre);
        $centre = $this->subjectsToCentreRepository->assignSubject($request->all(), $centre);
        return SubjectsToCentreResource::collection($centre->subjects)
            ->additional(['message' => trans('admin.centre_subject_updated')]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function orderSubject(Request $request, Centre $centre)
    {
        $this->authorize('update', $centre);
        $centre = $this->subjectsToCentreRepository->orderSubject($request->all(), $centre);
        return SubjectsToCentreResource::collection($centre->subjects()->orderBy('pivot_order', 'ASC')->get())
            ->additional(['message' => trans('admin.centre_subject_order')]);
    }

    /**
     * @param Request $request
     * @param Centre $centre
     *
     * @return [json]
     */
    public function removeSubject(Request $request, Centre $centre)
    {
        $this->authorize('delete', $centre);
        $this->subjectsToCentreRepository->removeSubject($request->all(), $centre);
        return response(['message' => trans('admin.centre_subject_deleted')]);
    }
    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function changeSubjectOrder(Request $request)
    {

        $this->subjectsToCentreRepository->changeSubjectOrder($request->all());
        return response(['message' => trans('admin.centre_subject_updated')]);
    }
}
