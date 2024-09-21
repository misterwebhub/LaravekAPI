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
/**
 * Class lessonRepository
 * @package App\Repositories
 */
class FeedbackRepository
{
    
     /**
     * add Feedbacks to lesson
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function addFeedback($request, $feedbacks)
    {       
        $feedbacks->content_id=$request['content_id'];
        $feedbacks->content_type=$request['content_type'];
        $feedbacks->feedback=$request['feedback'];
        return $feedbacks->save();
    }

    /**
     * list links corresponding to a lesson
     * @param mixed $lesson
     *
     * @return [json]
     */
}
