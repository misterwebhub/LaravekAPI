<?php

namespace App\Repositories\v1;

use App\Imports\StudentToCentreImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Models\Approval;

/**
 * Class StudentToCentreRepository
 * @package App\Repositories
 */
class StudentToCentreRepository
{
    /**
     * Import students in a centre
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function importStudentToCentre($request)
    {
        $approvalConst = $this->approvalConstCreate();
        $realPath = $request['student_centre_upload_file']->getRealPath();
        $validatedResult = checkStudentToCentreExcelFormat($realPath);
        if (!empty($validatedResult)) {
            $data['error'] = $validatedResult;
            throw ValidationException::withMessages(array("file" => $data));
        }
        $import = new StudentToCentreImport($request['centre_id'], $approvalConst);
        Excel::import($import, $request['student_centre_upload_file']);
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
}
