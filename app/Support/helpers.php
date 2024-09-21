<?php

use App\Models\Centre;
use App\Models\Organisation;
use App\Models\Subject;
use App\Models\Batch;
use App\Models\PhaseUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

function getTenant()
{
    $user = Auth::user();
    return $user->tenant_id;
}

/**
 * @param $names
 * @return mixed
 */
function getOrganisationFromName($names)
{
    return Organisation::whereIn('name', $names)->get(['id', 'name']);
}

/**
 * @param $names
 * @return mixed
 */
function getCentreFromOrgName($names)
{
    $organisations = Organisation::whereIn('name', $names)->get(['id']);
    return Centre::whereIn('organisation_id', $organisations)->get(['id', 'name', 'organisation_id']);
}

/**
 * @param $names
 * @return mixed
 */
function getSubjectFromName($names)
{
    return Subject::whereIn('name', $names)->get(['id', 'name']);
}

function checkStudentExcelFormat($realPath)
{
    $data = array_map('str_getcsv', file($realPath));
    $headingRow = current(array_slice($data, 1, 1));
    $fields = getHeadings();
    foreach ($fields as $key => $value) {
        $heading = isset($headingRow[$key]) ? $headingRow[$key] : '';
        if (!$heading) {
            return (trans('admin.excel_format_error') . ($key + 1));
        }

        if (!in_array($key, [21, 23, 34, 36, 49, 51]) && trim($heading) != $value) {
            return (trans('admin.excel_format_error') . ($key + 1) . ' ' . trim($heading));
        }
    }
}

function getHeadings()
{
    return array(
        'Id',
        'Name*',
        'Gender*',
        'DOB',
        'Email*',
        'Mobile*',
        'Trade/Course',
        'Educational Qualification',
        'Marital Status',
        'Work Experience in Years',
        'Last Monthly Salary Drawn',
        "Parent/Guardian's Name",
        "Parent/Guardian's Phone number",
        'Annual Family Income',
        'Guardian Occupation',
        'Contactability (*)',
        'If not Contactable, Reason (*)',
        'Interview 1 (Company Name)(*)',
        'Date of Interview 1 (*)',
        'Result of Interview 1 (*)',
        'Interview 2 (Company  ITI and VTI Trades list  Name)',
        'Date of Interview 2',
        'Results of Interview 2',
        'Interview 3 (Company Name)',
        'Date of Interview 3',
        'Results of Interview 3',
        'Placed (*)',
        'If interested for job, which month would you join?',
        'Date of updation (*)',
        'Remarks',
        'Batch completion Status',
        'Name of the Company',
        'Designation',
        'Location (District)',
        'Sector',
        'Offer Letter recieved',
        'Offer Letter type',
        'Gross Income  per Month',
        'Sector',
        'Gross Income  per Month',
        'Course',
        'Location',
        'Specify reason',
        'Status after 3 months',
        'Name of the Company',
        'Designation',
        'Location (District)',
        'Sector',
        'Offer Letter recieved',
        'Offer Letter type',
        'Gross Income  per Month',
        'Sector',
        'Gross Income  per Month',
        'Course',
        'Location',
        'Specify reason',
        'Email',
        'Mobile',
        'Status after 6 months',
        'Name of the Company',
        'Designation',
        'Location (District)',
        'Sector',
        'Offer Letter recieved',
        'Offer Letter type',
        'Gross Income  per Month',
        'Sector',
        'Gross Income  per Month',
        'Course',
        'Location',
        'Specify reason',
    );
}

function checkStudentToCentreExcelFormat($realPath)
{
    $data = array_map('str_getcsv', file($realPath));
    $headingRow = current(array_slice($data, 1, 1));
    $fields = getStudentToCentreHeadings();
    foreach ($fields as $key => $value) {
        $heading = isset($headingRow[$key]) ? $headingRow[$key] : '';
        if (!$heading) {
            return (trans('admin.excel_format_error') . ($key + 1));
        }

        if (!in_array($key, [21, 23, 34, 36, 49, 51]) && trim($heading) != $value) {
            return (trans('admin.excel_format_error') . ($key + 1) . ' ' . trim($heading));
        }
    }
}

function getStudentToCentreHeadings()
{
    return array(
        'Id',
        'Name',
        'Gender',
        'DOB',
        'Email',
        'Mobile',
        'Trade Or Course',
        'Educational Qualification',
        'Marital Status',
        'Work Experience in Years',
        'Last Monthly Salary Drawn',
        "Guardians Name",
        "Guardians Phone",
        'Annual Family Income',
        'Guardian Occupation',
        'Batch',
        'Contactability (*)',
        'If not Contactable, Reason (*)',
        'Interview 1 (Company Name)(*)',
        'Date of Interview 1 (*)',
        'Result of Interview 1 (*)',
        'Interview 2 (Company  ITI and VTI Trades list  Name)',
        'Date of Interview 2',
        'Results of Interview 2',
        'Interview 3 (Company Name)',
        'Date of Interview 3',
        'Results of Interview 3',
        'Placed (*)',
        'If interested for job, which month would you join?',
        'Date of updation (*)',
        'Remarks',
        'Batch Completion Status',
        'Company Name',
        'Designation',
        'Location',
        'Sector',
        'Offer Letter recieved',
        'Offer Letter type',
        'Income per Month',
        'Sector if Self Employed',
        'Income per Month If Self Employed',
        'Course',
        'Location If Higher studies',
        'Specify reason',
        'Status after 3 months',
        'Company Name After 3 months',
        'Designation After 3 months',
        'Location After 3 months',
        'Sector After 3 months',
        'Offer Letter recieved After 3 months',
        'Offer Letter type After 3 months',
        'Income per Month After 3 months',
        'Sector If Self Employed After 3 months',
        'Income per Month If Self Employed After 3 months',
        'Course After 3 months',
        'Location If Higher studies After 3 months',
        'Specify reason After 3 months',
        'Updated Email',
        'Updated Mobile',
        'Status after 6 months',
        'Company Name After 6 months',
        'Designation After 6 months',
        'Location After 6 months',
        'Sector After 6 months',
        'Offer Letter recieved After 6 months',
        'Offer Letter type After 6 months',
        'Income per Month After 6 months',
        'Sector If Self Employed After 6 months',
        'Income per Month If Self Employed After 6 months',
        'Course After 6 months',
        'Location If Higher studies After 6 months',
        'Specify reason After 6 months',
    );
}

function checkAdminUserExcelFormat($realPath)
{
    $data = array_map('str_getcsv', file($realPath));
    $headingRow = current(array_slice($data, 0, 1));
    $fields = getAdminUserHeadings();
    foreach ($fields as $key => $value) {
        $heading = isset($headingRow[$key]) ? $headingRow[$key] : '';
        if (!$heading) {
            return (trans('admin.excel_format_error') . ($key + 1));
        }

        if (!in_array($key, [21, 23, 34, 36, 49, 51]) && trim($heading) != $value) {
            return (trans('admin.excel_format_error') . ($key + 1) . ' ' . trim($heading));
        }
    }
    return [];
}
function getAdminUserHeadings()
{
    return array(
        'Id',
        'Name',
        'Email',
        'Mobile',
        'Role',
        'Centre/Orgs/Programs/Projects',
    );
}
function getImageTemporaryLink($image)
{
    if ($image) {
        $imageUrl = Storage::disk('s3')->temporaryUrl(
            $image,
            Carbon::now()->addMinutes(env('EXPIRY_LINK_TIME'))
        );
        return $imageUrl;
    }
}
function generateTempUrl($file)
{
    return Storage::disk('s3')->temporaryUrl(
        $file,
        Carbon::now()->addMinutes(10)
    );
}
function setUserPhase($user, $batch_id = null)
{
    if ($batch_id) {
        PhaseUser::where('user_id', $user->id)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
        $batch = Batch::where('id', $batch_id)->first();
        $batchPhases = $batch->phases()
            ->whereNull('phases.deleted_at')
            // ->where('start_date', '<=', Carbon::parse($user->created_at)->format('Y-m-d'))
            // ->where('end_date', '>=', Carbon::parse($user->created_at)->format('Y-m-d'))
            ->pluck('id');
        if ($batchPhases)
            $user->phases()->attach($batchPhases, ['centre_id' => $batch->centre_id, 'batch_id' => $batch_id]);
    } else {
        PhaseUser::where('user_id', $user->id)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
        $centre = $user->centre;
        $phases = $centre->phases()
            ->where('start_date', '<=', Carbon::parse($user->created_at)->format('Y-m-d'))
            ->where('end_date', '>=', Carbon::parse($user->created_at)->format('Y-m-d'))
            ->get();
        if (!empty($phases)) {
            $user->phases()->attach($phases, ['centre_id' => $centre->id]);
        }
    }
}
function removeUserPhase($user)
{
    // $user->phases()->detach();
    PhaseUser::where('user_id', $user->id)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
}
