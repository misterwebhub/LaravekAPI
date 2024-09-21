<?php

namespace App\Repositories\v1;

use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Course;
use App\Jobs\CourseAdded;
use App\Services\CourseSubjectCustomSort;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\AllowedFilter;
use App\Services\Filter\CourseCustomFilter;
use App\Models\SubjectCourse;

/**
 * [Description CourseRepository]
 */
class CourseRepository
{
    /**
     * List all courses
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request)
    {
        $courses = QueryBuilder::for(Course::class)
            ->allowedFilters(
                [
                    'name', 'subject.id', 'subject.name', 'order', 'description',
                    AllowedFilter::custom('search_value', new CourseCustomFilter()),
                ]
            )
            ->allowedSorts(
                [
                    'name', 'subject.id', 'order', 'description',
                    AllowedSort::custom('subject.name', new CourseSubjectCustomSort()),
                ]
            )
            ->where('tenant_id', getTenant())
            ->with('subject');


            if(isset($request['subject_id']) && $request['subject_id']!=''){
        
        $courses=$courses->where('subject_id',"".$request['subject_id']."");

        // $subjectId=$request['subject_id'];
        // $courses = Course::whereHas('subjectCourses', function ($query) use ($subjectId) {
        //     $query->where('subject_id', $subjectId);
        // })->get();
         $subjectId=$request['subject_id'];
$courses = Course::leftJoin('subject_courses', 'courses.id', '=', 'subject_courses.course_id')
    ->where('subject_courses.subject_id', $subjectId)
    ->orderBy('subject_courses.sort_order')
    ->select('courses.*','subject_courses.sort_order');

            }   




            $courses=$courses->latest()
            ->paginate($request['limit'] ?? null);
        return $courses;
    }

    /**
     * Create a new Course
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request)
    {
        $course = new Course();
        $course = $this->setCourse($request, $course);
        $course->save();

        $SubjectCourses=new SubjectCourse();
        $SubjectCourses->subject_id = $request['subject'];
        $SubjectCourses->course_id = $course->id;
        $SubjectCourses->sort_order = $course->order;
        $SubjectCourses->status = 1;
        $SubjectCourses=$SubjectCourses->save();
   
     

        CourseAdded::dispatch($course)->onQueue('notification');
        return $course;
    }



     /**
     * Assign a new Course top subject 
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function AddExistingCoursetoSubject($request)
    {
        
        // $course = new Course();
        // $course = $this->setCourse($request, $course);
        // $course->save();

        $CheckCourse=SubjectCourse::where([
            'course_id'=>$request['course_id'],
            'subject_id'=>$request['subject_id'],
        ])->first();

  

    if(!$CheckCourse){

        $course = Course::find($request['course_id']);

        $SubjectCourses=new SubjectCourse();
        $SubjectCourses->subject_id = $request['subject_id'];
        $SubjectCourses->course_id = $course->id;
       // $SubjectCourses->sort_order = $course->order;
        $SubjectCourses->status = 1;
        $SubjectCourses=$SubjectCourses->save();
        
         CourseAdded::dispatch($course)->onQueue('notification');
        return $course;
    }

    return $CheckCourse;
   
     

      
    }

    /**
     * Delete a Course
     * @param mixed $course
     *
     * @return [type]
     */
    public function destroy($course)
    {
        $course->delete();
    }

    /**
     * Update Course
     * @param mixed $request
     * @param mixed $course
     *
     * @return [json]
     */
    public function update($request, $course)
    {
        $course = $this->setCourse($request, $course);
        $course->update();
        return $course;
    }


    /**
     * Set Course Data
     * @param mixed $request
     * @param mixed $course
     *
     * @return [collection]
     */
    private function setCourse($request, $course)
    {
        $course->name = $request['name'];
        $course->tenant_id = getTenant();
        $course->subject_id = $request['subject'];
        $course->tag = $request['tag'];
        $course->is_mandatory = $request['is_mandatory'];
        $course->order = isset($request['order']) ? ($request['order'] ?: null) : null;
        $course->description = isset($request['description']) ? ($request['description'] ?: null) : null;
        return $course;
    }

     /**
     * @param mixed $request
     * @param mixed $course
     *
     * @return [type]
     */
    public function updateStatus($request, $course)
    {
        $course->status = $request['status'];
        $course->update();
        return $course;
    }
}
