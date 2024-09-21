<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CourseRequest;
use App\Http\Resources\v1\CourseResource;
use App\Models\Course;
use App\Repositories\v1\CourseRepository;
use Illuminate\Http\Request;

class CourseController extends Controller
{

    private $courseRepository;

    /**
     * @param CourseRepository $courseRepository
     */
    public function __construct(CourseRepository $courseRepository)
    {
        $this->courseRepository = $courseRepository;
        $this->middleware('permission:course.view', ['only' => ['index']]);
        $this->middleware('permission:course.create', ['only' => ['store']]);
        $this->middleware('permission:course.update', ['only' => ['show','update']]);
        $this->middleware('permission:course.destroy', ['only' => ['destroy']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $courses = $this->courseRepository->index($request->all());
        return CourseResource::collection($courses);
    }

    /**
     * @param CourseRequest $request
     *
     * @return [json]
     */
    public function store(CourseRequest $request)
    {
        $course = $this->courseRepository->store($request->all());
        return (new CourseResource($course))
            ->additional(['message' => trans('admin.course_added')]);
    }

    /**
     * @param Course $course
     *
     * @return [json]
     */
    public function show(Course $course)
    {
        return new CourseResource($course);
    }


    /**
     * @param CourseRequest $request
     * @param Course $course
     *
     * @return [json]
     */
    public function update(CourseRequest $request, Course $course)
    {
        $course = $this->courseRepository->update($request->all(), $course);
        return (new CourseResource($course))
            ->additional(['message' => trans('admin.course_updated')]);
    }


    /**
     * @param Course $course
     *
     * @return [json]
     */
    public function destroy(Course $course)
    {
        $this->courseRepository->destroy($course);
        return response(['message' => trans('admin.course_deleted')], 200);
    }
}
