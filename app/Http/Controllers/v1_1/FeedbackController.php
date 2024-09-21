<?php

namespace App\Http\Controllers\v1_1;

use App\Models\Feedback;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\v1\FeedbackRepository;
use App\Http\Requests\v1\StoreFeedbacksRequest;

class FeedbackController extends Controller
{
    private $FeedbacksRepository;

    public function __construct(FeedbackRepository $FeedbacksRepository)
    {
        $this->FeedbacksRepository = $FeedbacksRepository;
        $this->middleware('permission:lesson.view', ['only' => ['index', 'exportLesson']]);
        $this->middleware('permission:lesson.create', ['only' => ['store', 'importLesson']]);
        $this->middleware(
            'permission:lesson.update',
            ['only' => ['edit', 'update', 'updateStatus', 'addLink', 'listLink', 'importLesson']]
        );
        $this->middleware('permission:lesson.destroy', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreFeedbacksRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFeedbacksRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Feedback $feedbacks)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Feedback $feedbacks)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateFeedbacksRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFeedbacksRequest $request, Feedback $feedbacks)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Feedback $feedbacks)
    {
        //
    }

    /**
     * @param  mixed  $id
     * @return [type]
     */
    public function addFeedback(StoreFeedbacksRequest $request, Feedback $Feedbacks)
    {
        $checkSave = $this->FeedbacksRepository->addFeedback($request->all(), $Feedbacks);

        if (true == $checkSave) {
            $msg = [
                'status' => 200,
                'msg' => trans('admin.feedback_add'),
            ];
        } else {
            $msg = [
                'status' => 422,
                'msg' => trans('admin.feedback_add_err'),
            ];

        }

        return response()->json($msg);

    }

    /**
     * @param  getFeedbackByType  $request
     * @param  mixed  $id
     * @return [type]
     */
    public function getFeedbackByType(Request $request)
    {

        $segments = request()->segments();
        if (count($segments) >= 2) {
            $secondToLastSegment = $segments[count($segments) - 3];
            $content_id = $segments[count($segments) - 1];
        }

        $getFeedbacks = Feedback::where('content_type', '=', $secondToLastSegment)->where('content_id', '=', $content_id)->get();

        if (!$getFeedbacks->isEmpty()) {
            $msg = [
                'status' => 200,
                'data' => $getFeedbacks,
            ];
        } else {
            $msg = [
                'status' => 422,
                'msg' => trans('admin.feedback_search_err'),
            ];

        }

        return response()->json($msg);

    }
}
