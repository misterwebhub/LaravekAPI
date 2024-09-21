<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonsLinking extends Model
{
    use HasFactory;
    protected $table="lessons_linking";

       public function massUpdateOrders($data)
    {
        $updatdId=[];
        $isIssuefound=0;
        foreach ($data as $item) {
            $status=$this->where('course_id', $item['course_id'])->where('lesson_id', $item['lesson_id'])->update(['sort_order' => $item['order']]);
            if(!$status){
                 $isIssuefound=1;
                 array_push($updatdId,$item['lesson_id']);

            }
           
        }

        if($isIssuefound==1){
            return false;
        }
        else{
            return true;
        }
    }
}
