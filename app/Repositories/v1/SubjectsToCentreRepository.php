<?php

namespace App\Repositories\v1;

use App\Models\Subject;
use App\Models\CentreSubject;
use App\Models\Centre;

use Spatie\QueryBuilder\QueryBuilder;
use App\Jobs\SubjectAdded;


/**
 * Class SubjectsToCentreRepository
 * @package App\Repositories
 */
class SubjectsToCentreRepository
{
    /**
     * List all subjects corresponding to a centre in ascending order
     *
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [type]
     */
    public function index($request, $centre)
    {
        $subjectQuery = $centre->subjects();
        $subjects = QueryBuilder::for($subjectQuery)
            ->where('status', Subject::ACTIVE)
            ->distinct('name');
        $totalCount = $subjects->get()->count();

        $subjects = $subjects->allowedSorts(['name'])
            ->orderByRaw('ISNULL(pivot_order), pivot_order ASC')
            ->allowedFilters(['name'])
            ->paginate($request['limit'] ?? null);
        return ['subjects' => $subjects, 'total_count' => $totalCount];
    }

    /**
     * Assign subjects to a centre and batches
     *
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [type]
     */
    public function assignSubject($request, $centre)
    {
        $subjects = array_filter($request['subject']);
        $mappedSubjects = $centre->subjects->pluck('id')->toArray();
        $subjectDifference = array_diff($subjects, $mappedSubjects);
        $centre->subjects()->sync($subjects);
        $centreId = $centre->id;
        if ($centre->subjects()->sync($subjects)) {
            $this->assignTobatches($subjects, $centre);
        }

        if (!empty($subjectDifference)) {
            $this->centreSubjectJob($subjectDifference, $centreId);
            foreach ($subjectDifference as $subjectDiff) {
                $subjectOrder = Subject::where('id', $subjectDiff)->first();
                $centre->subjects()->updateExistingPivot($subjectDiff, array('order' => $subjectOrder->order));
            }
        }

        return $centre;
    }

    /**
     * Arrange the order of subjects corresponding to a centre
     *
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [type]
     */
    public function orderSubject($request, $centre)
    {
        if ($request['default'] == 0) {
            foreach ($request['subjects'] as $key => $subject) {
                $centre->subjects()->updateExistingPivot($subject, array('order' => $key + 1));
            }
        } else {
            $centre->subjects()->updateExistingPivot($request['subjects'], array('order' => 0));
        }
        return $centre;
    }

    /**
     * Remove subject from a centre
     *
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [type]
     */
    public function removeSubject($request, $centre)
    {
        $subjectId = $request['subject_id'];
        $centre->subjects()->detach($subjectId);
        
        $batches = $centre->batches()->get();
        foreach ($batches as $batch) {
            $batch->subjects()->detach($subjectId);
        }
    }

    /**
     * @param $centre
     * @param $request
     */
    private function centreSubjectJob($subjects, $centre)
    {
        foreach ($subjects as $subject) {
            $centreSubjectJob = array($centre, $subject);
            SubjectAdded::dispatch($centreSubjectJob)->onQueue('notification');
        }
        return $centreSubjectJob;
    }

    /**
     * Assign subjects to  batches
     * @param $centre
     * @param $request
     */
    private function assignTobatches($subjects, $centre)
    {
        $batches = $centre->batches()->get();
        foreach ($batches as $batch) {
            $batch->subjects()->sync($subjects);
        }

        return $batches;
    }

    public function changeSubjectOrder($request)
    {
        $centreSubjects = CentreSubject::where('order', 100000)
            ->orWhere('order', null)
            ->get();
        foreach ($centreSubjects as $centreSubject) {
            $centre = Centre::where('id', $centreSubject->centre_id)->where('deleted_at', null)->first();
            $subject = Subject::where('id', $centreSubject->subject_id)->where('deleted_at', null)->first();

            if (!empty($centre) && !empty($subject)) {
                $centre->subjects()->updateExistingPivot($subject, array('order' => $subject->order));
            }
        }
    }
}
