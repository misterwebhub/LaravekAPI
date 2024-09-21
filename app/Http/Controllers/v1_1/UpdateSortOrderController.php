<?php

namespace App\Http\Controllers\v1_1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CourseRequest;
use App\Http\Resources\v1\CourseResource;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Lesson;
use App\Repositories\v1\CourseRepository;
use Illuminate\Http\Request;
use App\Models\SubjectCourse;
use App\Models\LessonsLinking;


class UpdateSortOrderController extends Controller
{
    //

    /**
     * @param SortOrders $request
     *
     * @return [json]
     */
    public function updateOrder(Request $request)
    {
        $msgarray = $this->contentTypeSortOrder($request);
        return response()->json($msgarray);
    }

    public function contentTypeSortOrder($request)
    {
        $requestData = $request->all();
        switch ($requestData[0]['content_type']) {
            case ('1'):

                $Subject = Subject::find($request->id);
                if ($Subject) {
                    $Subject->order = $requestData['order'];

                    $Subject->save();

                    $msgarray = [
                        'status' => 200,
                        // 'msg'=>'updated successfully'
                        'msg' => trans('admin.sort_order_updated'),
                    ];
                } else {

                    $msgarray = [
                        'status' => 422,
                        // 'msg'=>'Subject Id not found'
                        'msg' => trans('admin.sort_order_notfound'),
                    ];

                }

                break;

            case (2):

                $Course = new SubjectCourse();

                $Course = $Course->massUpdateOrders($requestData);


                if ($Course) {

                    $msgarray = [
                        'status' => 200,
                        // 'msg'=>'course updated successfully'
                        'msg' => trans('admin.sort_order_course_updated'),
                    ];
                } else {
                    $msgarray = [
                        'status' => 422,
                        // 'msg'=>'course id not found or another technical issue'
                        'msg' => trans('admin.sort_order_course_notfound'),
                    ];
                }
                break;

            case (3):

                $LessonLinking = new LessonsLinking();
                $LessonLinkingStatus = $LessonLinking->massUpdateOrders($requestData);

                if ($LessonLinkingStatus) {

                    $msgarray = [
                        'status' => 200,
                        // 'msg'=>'Lesson updated successfully'
                        'msg' => trans('admin.sort_order_lesson_updated'),
                    ];
                    //  }
                } else {
                    $msgarray = [
                        'status' => 422,
                        // 'msg'=>'Lesson id not found or another technical issue'
                        'msg' => trans('admin.sort_order_lesson_notfound'),
                    ];
                }

                break;

            default:

                $msgarray = [
                    'status' => 402,
                    // 'msg'=>'Could not update order, Please try again or contact support '
                    'msg' => trans('admin.sort_order_default_notfound'),
                ];
        }
        return $msgarray;
    }
}


