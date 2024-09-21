<?php

namespace App\Repositories\v1;

use Illuminate\Validation\ValidationException;
use App\Exports\StudentBatchExport;
use App\Exports\PhaseStudentExport;
use App\Imports\StudentImport;
use App\Models\Approval;
use App\Models\Trade;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use App\Services\Filter\CentreAdminCustomFilter;

/**
 * Class CentreAdminRepository
 * @package App\Repositories
 */
class CentreAdminRepository
{
    /**
     * Export students in a batch
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function exportStudent($request, $user)
    {
        $filter['centre.administrator'] = $user->hasPermissionTo('centre.administrator');
        $filter['organisation.administrator'] = $user->hasPermissionTo('organisation.administrator');
        $filter['activity.needs.approval'] = $user->hasPermissionTo('activity.needs.approval');
        $filter['program.administrator'] = $user->hasPermissionTo('program.administrator');
        $filter['project.administrator'] = $user->hasPermissionTo('project.administrator');
        $filter['project_id'] = $request['filter']['project_id'] ?? null;
        $filter['centre_id'] = $request['filter']['centre_id'] ?? null;
        $filter['batch_id'] = $request['batch_id'] ?? null;
        $filter['organisation_id'] = $request['filter']['organisation_id'] ?? null;
        $fileName = "student_downlods/" . Carbon::now()->format('YmdHs') . "students.csv";
        Excel::store(new StudentBatchExport($filter), $fileName, 's3');
        return generateTempUrl($fileName);
    }

    /**
     * Export students in a batch
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function exportPhaseStudent($request)
    {
        $fileName = "student_phase_downlods/" . Carbon::now()->format('YmdHs') . "students.csv";
        Excel::store(new PhaseStudentExport($request['phase_id']), $fileName, 's3');
        return generateTempUrl($fileName);
    }

    /**
     * Import students in a batch
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function importStudent($request, $userId)
    {
        $approvalConst = $this->approvalConstCreate();
        $realPath = $request['student_upload_file']->getRealPath();
        $validatedResult = checkStudentExcelFormat($realPath);
        if (!empty($validatedResult)) {
            $data['error'] = $validatedResult;
            throw ValidationException::withMessages(array("file" => $data));
        }
        $import = new StudentImport($request['batch_id'], $approvalConst, $userId);
        Excel::import($import, $request['student_upload_file']);
        return $import->data;
    }

    public function approvalConstCreate()
    {
        $approvalConst = Str::uuid();
        $approval = Approval::where('reference_id', $approvalConst)->first();
        if ($approval) {
            return $this->approvalConstCreate();
        }
        return $approvalConst;
    }
    /**
     * List students in a batch for inline edit
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function listStudents($batchId, $request)
    {
        return QueryBuilder::for(User::class)
            ->with('studentDetail')
            ->whereHas('studentDetail', function ($q) use ($batchId) {
                $q->where('batch_id', '=', $batchId);
            })
            ->allowedFilters([
                AllowedFilter::custom('search_value', new CentreAdminCustomFilter())
            ])
            ->allowedSorts('name')
            ->where('tenant_id', getTenant())
            ->whereIn('type', array(User::TYPE_STUDENT, User::TYPE_ALUMNI))
            ->latest()
            ->paginate($request['limit'] ?? null);
    }

    /**
     * List trades corresponding to a centre type
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function getTrades($centerType)
    {
        return Trade::where('tenant_id', getTenant())
            ->where('type', $centerType)
            ->get();
    }

    /**
     * Update student details
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function updateStudent($request)
    {
        $user = User::where('id', $request['id'])->first();
        $student = $user->studentDetail;
        $student = $this->setStudent($request, $student);
        $student->update();
        return $user;
    }

    /**
     * Set student Data
     * @param mixed $request
     * @param mixed $student
     *
     * @return [collection]
     */
    private function setStudent($request, $student)
    {
        $student->educational_qualification_id = isset($request['educational_qualification_id']) ?
            ($request['educational_qualification_id'] ?: null) : null;
        $student->trade_id = isset($request['trade_id']) ? ($request['trade_id'] ?: null) : null;
        $student->marital_status = $request['marital_status'];
        $student->experience = $request['experience'];
        $student->last_month_salary = isset($request['last_monthly_salary']) ? $request['last_monthly_salary'] : null;
        $student->guardian_name = $request['guardian_name'];
        $student->guardian_mobile = $request['guardian_mobile'];
        $student->guardian_income = $request['guardian_income'];
        $student->guardian_occupation = isset($request['guardian_occupation']) ? $request['guardian_occupation'] : null;
        $student->updated_email = $request['updated_email'];
        $student->updated_mobile = $request['updated_mobile'];
        $student->contactability = isset($request['contactability']) && $request['contactability']!="" ? $request['contactability'] : null;
        $student->not_contactable_reason =
        isset($request['not_contactable_reason']) ? $request['not_contactable_reason'] : null;
        $student->interview1_company_name =
        isset($request['interview1_company_name']) ? $request['interview1_company_name'] : null;
        $student->interview1_date = isset($request['interview1_date']) ? ($request['interview1_date'] ?: null) : null;
        $student->interview1_result = isset($request['interview1_result']) ? $request['interview1_result'] : null;
        $student->interview2_company_name =
        isset($request['interview2_company_name']) ? $request['interview2_company_name'] : null;
        $student->interview2_date = isset($request['interview2_date']) ? ($request['interview2_date'] ?: null) : null;
        $student->interview2_result = isset($request['interview2_result']) ? $request['interview2_result'] : null;
        $student->interview3_company_name =
        isset($request['interview3_company_name']) ? $request['interview3_company_name'] : null;
        $student->interview3_date = isset($request['interview3_date']) ? ($request['interview3_date'] ?: null) : null;
        $student->interview3_result = isset($request['interview3_result']) ? $request['interview3_result'] : null;
        $student->placed = isset($request['placed']) ? $request['placed'] : null;
        $student->month_of_joining = isset($request['month_of_joining']) ? $request['month_of_joining'] : null;
        $student->date_of_updation = isset($request['date_of_updation']) ? ($request['date_of_updation'] ?: null) : null;
        $student->remarks = isset($request['remarks']) ? $request['remarks'] : null;
        return $student;
    }
}
