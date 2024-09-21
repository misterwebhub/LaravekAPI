<?php

namespace App\Repositories\v1;

use App\Models\Subject;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Sorts\SubjectProjectCustomSort;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\Filter\SubjectCustomFilter;
use Spatie\QueryBuilder\AllowedFilter;
use App\Services\SubjectCentreCountCustomSort;
use App\Models\SubjectLogs;

/**
 * [Description ProgramRepository]
 */
class SubjectRepository
{
    /**
     * List all subjects
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request)
    {

        $subjects = QueryBuilder::for(Subject::class)
            ->allowedFilters([
                'name', 'status', 'projects.name',
                AllowedFilter::custom('search_value', new SubjectCustomFilter())
            ])
            ->allowedSorts(
                [
                    'name', 'status',
                    AllowedSort::custom('centre_count', new SubjectCentreCountCustomSort()),

                ]
            )
            ->where('tenant_id', getTenant());

             if(isset($request['subject_id']) && $request['subject_id']!=''){
                $subjects=$subjects->where('id',$request['subject_id']);
                // $status=0;
            }

            else{

            if(isset($request['type']) && $request['type']=='draft'){
                $subjects=$subjects->where('status', 0);
                 $status=0;
            }
            else if(isset($request['type']) && $request['type']=='review'){
                $subjects=$subjects->where('status', 2);
                $status=2;

            }
            else{
                $subjects=$subjects->where('status', 1);
                $status=1;

            }

              $subjects=$subjects->with(['subject_user'])->with(['subject_user', 'subjectlogs' => function ($query) use ($status) {
            $query->where('status', $status);
            }]);


            // if status is published then only last sent review will work
            if($status==1){
                $subjects=  $subjects->with(['lastSentonReview'=>function ($query) use ($status) {
                $query->where('status',2)->latest();
            }]);
            }
            }     

             $subjects=$subjects->latest()
            ->paginate($request['limit'] ?? null);

            // if(isset($request['paginate']) && $request['paginate']==0){
            //     $subjects=$subjects->with(['subject_user'])->get();
            // }
            // else{

            //     $subjects=$subjects->with(['subject_user']);
            //      $subjects=$subjects->latest()
            // ->paginate($request['limit'] ?? null);
            // }
            
            // $subjects=$subjects->latest()
            // ->paginate($request['limit'] ?? null);
            //     $subjectlogs=subjectlogs::where([
            //         'status'=>2,
            //         'subject_id'=>
            //     ])->get();
            // $admin_author_collection = $admins->merge($authors);



        return $subjects;
    }

    /**
     * Create a new Subject
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request)
    {
        $subject = new Subject();
        $subject = $this->setSubject($request, $subject);

        
        $subject->save();
        return $subject;
    }

    /**
     * Delete a Centre
     * @param mixed $subject
     *
     * @return [type]
     */
    public function destroy($subject)
    {
        DB::beginTransaction();
        try {
            $subject->courses()->delete();
            $subject->lessons()->delete();
            $subject->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
        }
    }

    /**
     * Update Subject
     * @param mixed $request
     * @param mixed $subject
     *
     * @return [json]
     */
    public function update($request, $subject)
    {
        $subject = $this->setSubject($request, $subject);
        $subject->update();
        return $subject;
    }

    /**
     * Update status of program
     * @param mixed $request
     * @param mixed $subject
     *
     * @return [type]
     */
    public function updateStatus($request, $subject)
    {


    
        $subject->status = $request['status'];
        $SubjectID=$subject->update();


        if($SubjectID){
                   
         /*added Subject logs - date 13-0ct-2023 */
            $SubjectLogs = new SubjectLogs;
            $SubjectLogs->subject_id= $subject->id;
            $SubjectLogs->logged_user_id= $request['logged_user_id'];
            $SubjectLogs->status = $subject->status;
            $SubjectLogs->save();
         /*end Subject logs - date 13-0ct-2023 */         
         }

        if ($subject->status == Subject::IN_ACTIVE) {
            $subject->centres()->detach();
            $subject->batches()->detach();
        }

        return $subject;
    }

    /**
     * Set Subject Data
     * @param mixed $request
     * @param mixed $subject
     *
     * @return [collection]
     */
    private function setSubject($request, $subject)
    {
        $subject->name = $request['name'];
        $subject->description = $request['description'];
        $subject->tag = $request['tag'];
        $subject->image = $request['image'];
        $subject->subject_mandatory = $request['subject_mandatory'];
        $subject->tenant_id = getTenant();
        return $subject;
    }

    /**
     * Arrange the order of subjects
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function arrangeSubjectOrder($request)
    {
        foreach ($request['subjects'] as $key => $subjectId) {
            $subject = Subject::where('id', $subjectId)->first();
            if ($subject->order != $key + 1) {
                $subject->order = $key + 1;
                $subject->update();
            }
        }
    }

     /**
     * Arrange the order of subjects
     *
     * @param mixed $SubjectRepository Order
     *
     * @return [type]
     */

}
