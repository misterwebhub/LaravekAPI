<?php

namespace App\Repositories\v1;

use App\Exports\LessonExport;
use App\Imports\LessonImport;
use App\Models\Language;
use App\Models\LanguageLesson;
use App\Models\Lesson;
use App\Models\LessonType;
use App\Models\Subject;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\LessonSubjectCustomSort;
use App\Services\LessonCourseCustomSort;
use App\Services\Filter\LessonCustomFilter;
use ZipArchive;
use Illuminate\Support\Facades\File;
use App\Models\LessonsLinking;
/**
 * Class lessonRepository
 * @package App\Repositories
 */
class LessonRepository
{
    /**
     * Create new lesson
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function create()
    {
        $data['subject'] = Subject::select('id', 'name')->where('status', 1)->get();
        $data['lesson_type'] = LessonType::select('id', 'name', 'is_portrait_view')->get();
        return $data;
    }

    /**
     * List all lessons
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request)
    {
        $lessons = QueryBuilder::for(Lesson::class)
            ->allowedFilters(
                [
                    'name', 'subject.name', 'description', 'course.name',
                    AllowedFilter::exact('subject_id'),
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('course_id'),
                    AllowedFilter::custom('search_value', new LessonCustomFilter()),
                ]
            )
            ->allowedSorts(
                [
                    'name', 'description', 'status',
                    AllowedSort::custom('subject.name', new LessonSubjectCustomSort()),
                    AllowedSort::custom('course.name', new LessonCourseCustomSort()),
                ]
            )
            ->with('lessonType', 'lessonCategory', 'subject', 'course')
            ->where('lessons.tenant_id', getTenant());

            $filter='';
            if(isset($request['filter']['course_id']) && $request['filter']['course_id']!=''){
                // $lessons=$lessons->where('course_id',"".$request['filter']['course_id']."");
                $filter=['lessons.course_id','=',$request['filter']['course_id']];
            }    



            if(isset($request['filter']['subject_id']) && $request['filter']['subject_id']!=''){
                // $lessons=$lessons->where('subject_id',"".$request['filter']['subject_id']."");

                $filter=['lessons.subject_id','=',$request['filter']['subject_id']];
            }    


            
            if(isset($request['course_id']) && $request['course_id']!=''){
                // $lessons=$lessons->where('course_id',"".$request['filter']['course_id']."");
                $filter=['lessons.course_id','=',$request['course_id']];
            }    



            if(isset($request['subject_id']) && $request['subject_id']!=''){
                // $lessons=$lessons->where('subject_id',"".$request['filter']['subject_id']."");

                $filter=['lessons.subject_id','=',$request['subject_id']];
            }    


    if($filter){
        $lessons = Lesson::leftJoin('lessons_linking', 'lessons_linking.lesson_id', '=', 'lessons.id')    
    ->where([$filter])
    ->orderBy('lessons_linking.sort_order')
    ->select('lessons.*','lessons_linking.sort_order'); 
          
            }

           // dd($lessons->toSql());

            $lessons=$lessons->latest()
            ->paginate($request['limit'] ?? null);
        return $lessons;
    }

    /**
     * Create a new Lesson
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request)
    {
        $lesson = new Lesson();
        $lesson = $this->setlesson($request, $lesson);
        $lesson->save();

        $LessonsLinking=new LessonsLinking();

        $LessonsLinking->subject_id = $request['subject'];
        $LessonsLinking->course_id = $request['course'];
        $LessonsLinking->lesson_id = $lesson->id;

        // $SubjectCourses->sort_order = $course->order;
        $LessonsLinking->status = 1;
        $LessonsLinking=$LessonsLinking->save();
        return $lesson;
    }

    /**
     * Delete an lesson
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy($lesson)
    {
        $lesson->delete();
    }

    /**
     * Update lesson
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function update($request, $lesson)
    {
        $lesson = $this->setlesson($request, $lesson);
        $lesson->update();
        return $lesson;
    }

    /**
     * Set lesson Data
     * @param mixed $request
     * @param mixed $lesson
     *
     * @return [collection]
     */
    private function setlesson($request, $lesson)
    {

      
      $file = $request['fileInput'];
   
       //Display File Name
       //echo 'File Name: '.$file->getClientOriginalName();
       //echo '<br>';

       //Display File Extension
      // echo 'File Extension: '.$file->getClientOriginalExtension();
       //echo '<br>';

      /*
    if($file->getClientOriginalExtension()=='zip'){

        $zip = new ZipArchive();
        $status = $zip->open($file->getRealPath());

        if ($status !== true) {
         throw new \Exception($status);
        }
        else{
          
            $storageDestinationPath= storage_path("app/unzip/".$file->getClientOriginalName()."-".time()."/");
       
            if (!\File::exists( $storageDestinationPath)) {
                \File::makeDirectory($storageDestinationPath, 0755, true);
            }
            $zip->extractTo($storageDestinationPath);
            $zip->close();
    

        }
        if(file_exists($storageDestinationPath.'/shared/scormfunctions.js')){            

         }else{
         return json_encode(['status'=>0,'msg'=>'Scorp file format is not correct']);
         }
    }
    */

    //die;

        $lesson->name = $request['name'];        
        $lesson->filInput=$file;
        $lesson->subject_id = $request['subject'];
        $lesson->course_id = $request['course'];
        $lesson->point = isset($request['point']) ?
            ($request['point'] ?: null) : null;
        $lesson->lesson_type_id = $request['lesson_type'];
        $lesson->lesson_category_id = $request['lesson_category'];
        $lesson->is_assessment = isset($request['is_assessment']) ?
            ($request['is_assessment'] ?: null) : null;
        $lesson->assessment_question = $request['assessment_question'];
        $lesson->web_access = isset($request['web_access']) ?
            ($request['web_access'] ?: null) : null;
        $lesson->mobile_access = isset($request['mobile_access']) ?
            ($request['mobile_access'] ?: null) : null;
        $lesson->student_access = isset($request['student_access']) ?
            ($request['student_access'] ?: null) : null;
        $lesson->facilitator_access = isset($request['facilitator_access']) ?
            ($request['facilitator_access'] ?: null) : null;
        $lesson->mastertrainer_access = isset($request['mastertrainer_access']) ?
            ($request['mastertrainer_access'] ?: null) : null;
        $lesson->lesson_order = isset($request['lesson_order']) ?
            ($request['lesson_order'] ?: null) : null;
        $lesson->is_portrait_view = isset($request['is_portrait_view']) ?
            ($request['is_portrait_view'] ?: null) : null;
        $lesson->tenant_id = getTenant();
        $lesson->description = isset($request['description']) ?
            ($request['description'] ?: null) : null;
        $lesson->duration = $request['duration'] ?? null;

         $lesson->tag = isset($request['tag']) ?
            ($request['tag'] ?: null) : null;

             $lesson->is_mandatory = isset($request['is_mandatory']) ?
            ($request['is_mandatory'] ?: 0) : 0;

               $lesson->language_id = isset($request['language_id']) ?
            ($request['language_id'] ?: Null) : 0;

        return $lesson;
    }

    /**
     * @param mixed $request
     * @param mixed $lesson
     *
     * @return [type]
     */
    public function updateStatus($request, $lesson)
    {
        $lesson->status = $request['status'];
        $lesson->update();
        return $lesson;
    }

    /**
     * add links to lesson
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function addLink($request, $lesson)
    {
        $syncData = [];
        foreach ($request['link'] as $key => $link) {
            if (!empty($link['folder_path']) || !empty($link['download_path']) || !empty($link['index_path'])) {
                $syncData[$key] = $link;
                $syncData[$key]['tenant_id'] = getTenant();
            }
        }
        $lesson->touch();
        $lesson->subject()->touch();
        return $lesson->lessonLinks()->sync($syncData);
    }

    /**
     * list links corresponding to a lesson
     * @param mixed $lesson
     *
     * @return [json]
     */
    public function listLink($lesson)
    {
        $languages = Language::orderBy("order", 'ASC')->get();
        foreach ($languages as $key => $language) {
            $langLesson = LanguageLesson::where('language_id', $language->id)->where('lesson_id', $lesson->id)->first();
            if ($langLesson) {
                $data[$key]['language_id'] = $language->id;
                $data[$key]['language'] = $language->name;
                $data[$key]['index_path'] = $langLesson->index_path;
                $data[$key]['folder_path'] = $langLesson->folder_path;
                $data[$key]['download_path'] = $langLesson->download_path;
            } else {
                $data[$key]['language_id'] = $language->id;
                $data[$key]['language'] = $language->name;
                $data[$key]['index_path'] = "";
                $data[$key]['folder_path'] = "";
                $data[$key]['download_path'] = "";
            }
        }
        return $data;
    }



  /**
     * list links corresponding to a lesson
     * @param mixed $lesson
     *
     * @return [json]
     */
    public function AllLanglistLink()
    {
        $languages = Language::orderBy("order", 'ASC')->get();
        foreach ($languages as $key => $language) {
            $langLesson = LanguageLesson::where('language_id', $language->id)->first();
            if ($langLesson) {
                $data[$key]['language_id'] = $language->id;
                $data[$key]['language'] = $language->name;
                $data[$key]['index_path'] = $langLesson->index_path;
                $data[$key]['folder_path'] = $langLesson->folder_path;
                $data[$key]['download_path'] = $langLesson->download_path;
            } else {
                $data[$key]['language_id'] = $language->id;
                $data[$key]['language'] = $language->name;
                $data[$key]['index_path'] = "";
                $data[$key]['folder_path'] = "";
                $data[$key]['download_path'] = "";
            }
        }
        return $data;
    }


   
    /**
     * Export lessons
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function exportLesson($request)
    {
        $fileName = "lesson_downlods/" . time() . "lesson.csv";
        Excel::store(new LessonExport(), $fileName, 's3');
        return generateTempUrl($fileName);
    }

    /**
     * Import lessons
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function importLesson($request)
    {
        $import = new LessonImport();
        Excel::import($import, $request['lesson_upload_file']);
        return $import->data;
    }


     /**
     * add Feedbacks to lesson
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function addFeedback($request, $lessonfeedback)
    {
       
        $lessonfeedback->lesson_id=$request['lesson_id'];
        $lessonfeedback->feedback=$request['feedback'];
        return $lessonfeedback->save();
    }

    /**
     * list links corresponding to a lesson
     * @param mixed $lesson
     *
     * @return [json]
     */




     /**
     * Assign a new AddExistingLesson top subject 
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function AddExistingLesson($request)
    {
        

        $CheckLesson=LessonsLinking::where([
            'course_id'=>$request['course_id'],
            'lesson_id'=>$request['lesson_id'],
        ])->first();

  

    if(!$CheckLesson){

        $Lesson = Lesson::find($request['lesson_id']);

        $LessonsLinking=new LessonsLinking();
        $LessonsLinking->subject_id = $request['subject_id'];
        $LessonsLinking->course_id = $request['course_id'];
        $LessonsLinking->lesson_id = $Lesson->id;

        // $SubjectCourses->sort_order = $course->order;
        $LessonsLinking->status = 1;
        $LessonsLinking=$LessonsLinking->save();
        
         // CourseAdded::dispatch($course)->onQueue('notification');
        return $Lesson;
    }

    return $CheckLesson;
   
     }
}
