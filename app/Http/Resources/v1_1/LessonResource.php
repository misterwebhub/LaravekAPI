<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Language;
class LessonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {


        if (!$this->name) {
            return parent::toArray($request);
        }


        $lang_name = Language::where('id',$this->language_id)->first();

        if($lang_name){
            $langname=$lang_name->name;
        }
        else{
            $langname=null;
        }
      

        return [
            'id' => $this->id,
            'name' => $this->name,
            'filInput'=>$this->filInput,
            'subject_id' => $this->subject_id,
            'subject_name' => $this->subject->name ?? null,
            'course_id' => $this->course_id,
            'course_name' => $this->course->name ?? null,
            'lesson_type_id' => $this->lesson_type_id,
            'lesson_type' => $this->lessonType->name ?? null,
            'lesson_category_id' => $this->lesson_category_id,
            'lesson_category' => $this->lessonCategory->name ?? null,
            'point_alloted' => $this->point,
            'assessment_question' => $this->assessment_question ?? null,
            'status' => $this->status,
            'is_assessment' => $this->is_assessment,
            'web_access' => $this->web_access,
            'mobile_access' => $this->mobile_access,
            'student_access' => $this->student_access,
            'facilitator_access' => $this->facilitator_access,
            'mastertrainer_access' => $this->mastertrainer_access,
            'lesson_order' => $this->lesson_order,
            'is_portrait_view' => $this->is_portrait_view,
            'description' => $this->description,
            'duration' => $this->duration,
             'language_id' => $this->language_id,
             'tag' => $this->tag,
             'is_mandatory' => $this->is_mandatory,
             'lang_name' => $langname,
               
 
            
        ];
    }
}
