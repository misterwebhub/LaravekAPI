<?php

namespace App\Repositories\v1;

use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Phase;
use Carbon\Carbon;
use App\Models\PhaseSubjectCustomOrder;
use Illuminate\Support\Facades\DB;

/**
 * [Description ProgramRepository]
 */
class PhaseRepository
{
    /**
     * List all Phases
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request)
    {


        $phases = QueryBuilder::for(Phase::class)
            ->allowedFilters(['name'])
            ->allowedSorts(['name', 'start_date', 'end_date'])
            ->where('tenant_id', getTenant())
            ->with('projects') 
            ->latest();

             if (isset($request['no_pagination']) && $request['no_pagination']==1) {
        // If 'no_pagination' parameter is present in the request, fetch all records without pagination
        $phases = $phases->get();
    } else {
        // Paginate the results (or use $request->input('limit') as you did)
        $phases = $phases->paginate($request['limit'] ?? null);
    }
        return $phases;
    }

    /**
     * Create a new Phase
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request)
    {
        $phase = new Phase();
        $phase = $this->setPhase($request, $phase);
        $phase->save();
        return $phase;
    }

    /**
     * Delete a Phase
     * @param mixed $Phase
     *
     * @return [type]
     */
    public function destroy($phase)
    {
        $phase->delete();
    }

    /**
     * Update Phase
     * @param mixed $request
     * @param mixed $Phase
     *
     * @return [json]
     */
    public function update($request, $phase)
    {
        $phase = $this->setPhase($request, $phase);
        $phase->update();
        return $phase;
    }

    /**
     * Set Phase Data
     * @param mixed $request
     * @param mixed $Phase
     *
     * @return [collection]
     */
    private function setPhase($request, $phase)
    {
        $phase->name = $request['name'];
        $phase->target_students = isset($request['target_students']) ? (
            ($request['target_students']) ?: null) : null;
        $phase->target_trainers = isset($request['target_trainers']) ? (
            ($request['target_trainers']) ?: null) : null;
        $phase->tenant_id = getTenant();
        $phase->start_date = $request['start_date'];
        $phase->end_date = $request['end_date'];
        return $phase;
    }

    /**
     * List all Phases between a date range
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function getPhaseList($request)
    {
        $start_date = Carbon::parse($request['start_date'])->format('Y-m-d');
        $end_date = Carbon::parse($request['end_date'])->format('Y-m-d');
        $phases = QueryBuilder::for(Phase::class)
            ->where('tenant_id', getTenant())
            ->where('start_date', "<=", $start_date)
            ->where('end_date', ">=", $start_date)
            ->latest()
            ->paginate($request['limit'] ?? null);
        return $phases;
    }

    /**
     * List all phases corresponding to a batch
     *
     * @param mixed $request
     * @param mixed $batch
     *
     * @return [type]
     */
    public function listPhase($request, $batch, $user)
    {
        $phaseQuery = $batch->phases();
        $phases = QueryBuilder::for($phaseQuery)
            ->latest();
        $totalCount = $phases->count();
        $phases = $phases->allowedFilters([
            'name',
        ]);
        if (isset($request['limit'])) {
            $phases = $phases->paginate($request['limit'] ?? null);
        } else {
            $phases = $phases->get();
        }
        return ['phases' => $phases, 'total_count' => $totalCount];
    }

    /**
     * Assign a phases corresponding to a batch
     *
     * @param mixed $request
     * @param mixed $batch
     *
     * @return [type]
     */
    public function assignPhase($request, $batch)
    {
        $phases = array_filter($request['phase']);
        if (!empty($phases)) {
            $batch->phases()->sync($phases);
        }
        return $batch;
    }

    public function getSubjectsByPhase($phaseId)
    {
       $phase = Phase::find($phaseId);

        return response()->json(['status'=>1,'data'=>$phase->subjects]);
    }

     /**
     * Assign a phases corresponding to a batch
     *
     * @param mixed $request
     * @param mixed $batch
     *

        if (!$phase) {
            return response()->json(['error' => 'Phase not found'], 404);
        }

        $subjects = $phase->subjects;
     * @return [type]
     */
  public function assignPhaseWithSubjects($request, $phaseId)
    {
        $subjects = array_filter($request['subject']);
        if (!empty($subjects)) {
              $phase = Phase::find($phaseId);
            $currentSubjects = $phase->subjects()->pluck('subject_id')->toArray();
            $deletedSubjects = array_diff($currentSubjects, $subjects);
            $phase->subjects()->sync($subjects);
       
        }
        return $phase->subjects;

        
    }



    public function arrangeSubjectCustomOrder($request, $phaseId)
    {
        $subjectsData = $request;
       //   dd($subjectsData['subjects']);
        foreach ($subjectsData['subjects'] as $key=>$subject) {
            $order=$key+1;

            $existingSubject = PhaseSubjectCustomOrder::where('phase_id', $phaseId)
            ->where('subject_id', $subject['id'])->first();

            if ($existingSubject) {
                // Update the order
                $existingSubject->subject_order = $order;
                $existingSubject->save();
            } else {
                // Create a new row with order and created_at
                PhaseSubjectCustomOrder::create([
                    'phase_id' => $phaseId,                    
                    'subject_id' => $subject['id'],
                  
                    'subject_order' => $order,
                    'created_at' => now(),
                    // Add other fields as needed
                ]);
            }
        }

        return response()->json(['status'=>1,'message' => 'Set Custom Order successfully']);
    }



 public function getPhaseSubjectsCustomOrder($request, $phase)
        { 
        $subjects = DB::table('phase_subject_custom_order')
        ->join('subjects', 'phase_subject_custom_order.subject_id', '=', 'subjects.id')
         ->select(
            'subjects.id as id',
            'subjects.name as name',
            'phase_subject_custom_order.subject_order as order',
            'phase_subject_custom_order.created_at',
            'phase_subject_custom_order.subject_id'
        )
        ->where('phase_subject_custom_order.phase_id',$phase)
        ->orderBy('phase_subject_custom_order.subject_order')
        ->get();

        return response()->json(['status' => 1, 'data'=>$subjects]);
    }



}
