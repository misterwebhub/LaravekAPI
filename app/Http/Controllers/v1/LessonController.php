<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\LessonLinkRequest;
use App\Http\Requests\v1\LessonRequest;
use App\Http\Requests\v1\LessonFeedbackRequest;

use App\Http\Requests\v1\UpdateStatusLessonRequest;
use App\Http\Requests\v1\LessonImportRequest;
use App\Http\Resources\v1\LessonLanguageResource;
use App\Http\Resources\v1\LessonResource;
use App\Models\Lesson;
use App\Models\LessonFeedback;
use App\Repositories\v1\LessonRepository;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    private $lessonRepository;
    /**
     * @param LessonRepository $lessonRepository
     */
    public function __construct(LessonRepository $lessonRepository)
    {
        $this->lessonRepository = $lessonRepository;
        $this->middleware('permission:lesson.view', ['only' => ['index', 'exportLesson']]);
        $this->middleware('permission:lesson.create', ['only' => ['store', 'importLesson']]);
        $this->middleware(
            'permission:lesson.update',
            ['only' => ['edit', 'update', 'updateStatus', 'addLink', 'listLink', 'importLesson']]
        );
        $this->middleware('permission:lesson.destroy', ['only' => ['destroy']]);
    }
    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function create()
    {
        $lesson = $this->lessonRepository->create();
        return response($lesson, 200);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $lessons = $this->lessonRepository->index($request->all());
        return LessonResource::collection($lessons);
    }

    /**
     * @param LessonRequest $request
     *
     * @return [type]
     */
    public function store(LessonRequest $request)
    {

        $lesson = $this->lessonRepository->store($request->all());
        return (new LessonResource($lesson))
            ->additional(['message' => trans('admin.lesson_added')]);
    }

    /**
     * @param mixed $id
     *
     * @return [type]
     */
    public function edit(Lesson $lesson)
    {
        return new LessonResource($lesson);
    }

    /**
     * @param LessonRequest $request
     * @param mixed $id
     *
     * @return [type]
     */
    public function update(LessonRequest $request, Lesson $lesson)
    {
        $lesson = $this->lessonRepository->update($request->all(), $lesson);
        return (new LessonResource($lesson))
            ->additional(['message' => trans('admin.lesson_updated')]);
    }

    /**
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy(Lesson $lesson)
    {
        $this->lessonRepository->destroy($lesson);
        return response(['message' => trans('admin.lesson_deleted')]);
    }
    /**
     * @param UpdateStatusLessonRequest $request
     * @param Lesson $lesson
     *
     * @return [type]
     */
    public function updateStatus(UpdateStatusLessonRequest $request, Lesson $lesson)
    {
        $lesson = $this->lessonRepository->updateStatus($request->all(), $lesson);


        return (new LessonResource($lesson))
            ->additional(['message' => trans('admin.lesson_status_change')]);
    }
    /**
     * @param LessonRequest $request
     * @param mixed $id
     *
     * @return [type]
     */
    public function addLink(LessonLinkRequest $request, Lesson $lesson)
    {
        $this->lessonRepository->addLink($request->all(), $lesson);
        return LessonLanguageResource::collection($lesson->lessonLinks)
            ->additional(['message' => trans('admin.lesson_link_updated')]);
    }


    public function listLink(Lesson $lesson)
    {
        $lessonLinks = $this->lessonRepository->listLink($lesson);
        return response([
            'data' => $lessonLinks,
        ], 200);
    }






    // public function show()
    //   {

    //       $lessonLinks = $this->lessonRepository->AllLanglistLink();
    //       return response([
    //           'data' => $lessonLinks,
    //       ], 200);

    //     }

    public function allanglistLink()
    {

        $lessonLinks = $this->lessonRepository->AllLanglistLink();
        return response([
            'data' => $lessonLinks,
        ], 200);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function exportLesson(Request $request)
    {
        $filePath = $this->lessonRepository->exportLesson($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function importLesson(LessonImportRequest $request)
    {
        $importData = $this->lessonRepository->importLesson($request->all());
        return response([
            'data' => $importData,
        ], 200);
    }



    /**
     * @param LessonFeedbackRequest $request
     * @param mixed $id
     *
     * @return [type]
     */
    public function addFeedback(LessonFeedbackRequest $request, LessonFeedback $LessonFeedback)
    {
        $CheckSave = $this->lessonRepository->addFeedback($request->all(), $LessonFeedback);


        if ($CheckSave == true) {
            $msg = [
                'status' => 200,
                'msg' => trans('admin.feedback_add')
            ];
        } else {
            $msg = [
                'status' => 422,
                'msg' => trans('admin.feedback_add_err')
            ];

        }

        return response()->json($msg);

    }

    /**
     * @param Add to Existing lesson $request
     *
     * @return [json]
     */
    public function AddExistingLesson(Request $request)
    {

        $Lessons = $this->lessonRepository->AddExistingLesson($request->all());
        return (new LessonResource($Lessons))
            ->additional(['message' => trans('admin.lesson_added')]);
    }
}
