<?php

namespace App\Repositories\v1;

use App\Models\LearningActivity;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Jobs\LessonCompleted;
use App\Models\SubjectFeedback;
use Carbon\Carbon;

/**
 * Class StudentMergeRepository
 * @package App\Repositories
 */
class StudentMergeRepository
{
    /**
     * Student Merge
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function studentMerge($request)
    {
        $fromStudentId = $request['from_student'];
        $toStudentId = $request['to_student'];
        $fromStudentDetails = User::find($fromStudentId);
        $toStudentDetails = User::find($toStudentId);
        DB::table('phase_users')
            ->where('user_id', $fromStudentId)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
        $toSubjects = DB::table('centre_subject')
            ->where('centre_id', $toStudentDetails->centre_id)->pluck('subject_id');
        $toLessons = Lesson::whereIn('subject_id', $toSubjects)->pluck('id');
        $fromStudentActivity = $fromStudentDetails->activity()->whereIn('lesson_id', $toLessons)->get();
        if (count($fromStudentActivity) > 0) {
            $fromStudentActivity->map(function ($item, $key) use ($toStudentId) {
                return $item->user_id = $toStudentId;
            });
            foreach ($fromStudentActivity as $activity) {
                $activityDetails = LearningActivity::where('lesson_id', $activity->lesson_id)
                    ->where('user_id', $activity->user_id)->first();
                if ($activityDetails) {
                    if (
                        $activityDetails->completed == LearningActivity::LESSON_NOT_COMPLETED
                        || $activityDetails->completed == null
                    ) {
                        $updateActivity = $this->setLearningActivity($activityDetails, $activity);
                        $updateActivity->update();
                    }
                } else {
                    $learningActivity = new LearningActivity();
                    $createActivity = $this->setLearningActivity($learningActivity, $activity);
                    $createActivity->centre_id = $toStudentDetails->centre_id;
                    $createActivity->save();
                    if ($activity->completed == LearningActivity::LESSON_COMPLETED) {
                        LessonCompleted::dispatch($activity)->onQueue('activity');
                    }
                }
            }
        }
        $this->updateSubjectFeedback($fromStudentId, $toStudentId);
        if (isset($fromStudentDetails->studentDetail)) {
            $fromStudentDetails->studentDetail->delete();
        }
        $fromStudentDetails->delete();
        $data['message'] = trans('admin.merge_completed');
        return $data;
    }

    private function setLearningActivity($learningActivity, $activity)
    {
        $learningActivity->user_id = $activity->user_id;
        $learningActivity->subject_id = $activity->subject_id;
        $learningActivity->course_id = $activity->course_id;
        $learningActivity->lesson_id = $activity->lesson_id;
        $learningActivity->tenant_id = $activity->tenant_id;
        $learningActivity->completed = $activity->completed;
        $learningActivity->result = $activity->result;
        $learningActivity->score = $activity->score;
        $learningActivity->language_id = $activity->language_id;
        $learningActivity->activity = $activity->activity;
        return $learningActivity;
    }
    private function updateSubjectFeedback($fromStudentId, $toStudentId)
    {
        $subjectFeedbacks = SubjectFeedback::where('user_id', $fromStudentId)->get();
        if (count($subjectFeedbacks) > 0) {
            foreach ($subjectFeedbacks as $feedback) {
                $feedbackDetails = SubjectFeedback::where('subject_id', $feedback->subject_id)
                    ->where('user_id', $toStudentId)->first();
                if (!$feedbackDetails) {
                    $feedback->user_id = $toStudentId;
                    $feedback->update();
                }
            }
        }
    }
}
