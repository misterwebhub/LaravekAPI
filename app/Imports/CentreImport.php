<?php

namespace App\Imports;

use App\Exports\CentreSuccessErrorExport;
use App\Models\Centre;
use App\Models\CentreType;
use App\Models\District;
use App\Models\Organisation;
use App\Models\PartnershipType;
use App\Models\Project;
use App\Models\State;
use App\Models\Subject;
use App\Models\Approval;
use Hamcrest\Type\IsNumeric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class OrganisationDataImport
 * @package App\Imports
 */
class CentreImport implements ToCollection, WithHeadingRow
{
    public $data;
    protected $approvalConst;

    public function __construct($approvalConst)
    {
        $this->approvalConst = $approvalConst;
    }
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $finalResultAll = [];
        $finalResultErrors = [];

        $keyArray = array(
            0 => 'id',
            1 => 'centre_name',
            2 => 'centre_id',
            3 => 'organisation_name',
            4 => 'projects',
            5 => 'centre_type',
            6 => 'type_of_partnership',
            7 => 'target_students',
            8 => 'target_trainers',
            9 => 'working_mode',
            10 => 'location',
            11 => 'email',
            12 => 'mobile',
            13 => 'website',
            14 => 'address',
            15 => 'state',
            16 => 'district',
            17 => 'city',
            18 => 'status',
        );
        $errorCount = 0;
        foreach ($collection->chunk(10) as $chunk) {
            foreach ($chunk as $row) {
                $finalRes = [];
                $errors = [];
                $projectIds = [];
                $this->checkExcelFormat($row, $keyArray);
                $centreDetails = $this->fetchdata($row);

                if ($row['projects'] != "") {
                    $projectDetails = $this->getProject($row['projects']);
                    $projectIds = $projectDetails[1]['project_ids'];

                    if (!empty($projectDetails[0]['project_errors'])) {
                        $errors[] = $projectDetails[0]['project_errors'];
                    }
                }
                foreach ($row as $value) {
                    $finalRes[] = $value;
                }
                if ($row['id']) {
                    $centre = Centre::where('id', $row['id'])->first();
                    $errors = $this->validateFields($row['id'], $centreDetails, $errors);
                } else {
                    $centre = new Centre();
                    $errors = $this->validateFields("", $centreDetails, $errors);
                }
                if (empty($errors)) {
                    $centre = $this->setcentre($centre, $centreDetails);
                    if ($row['id']) {
                        $centre->update();
                    } else {
                        if (auth()->user()->hasPermissionTo('activity.needs.approval')) {
                            $approvalReferenceId = Approval::where('reference_id', $this->approvalConst)->first();
                            if (empty($approvalReferenceId)) {
                                $approvalReferenceId = $this->setApproval();
                            }
                            $centre->is_approved = Organisation::TYPE_NOT_APPROVED;
                            $centre->approval_group = $approvalReferenceId->reference_id;
                        }
                        $centre->save();
                    }
                    DB::commit();
                    $centre->projects()->syncWithoutDetaching($projectIds);
                    foreach ($projectIds as $projectId) {
                        $project = Project::where('id', $projectId)->first();
                        $subjects = $project->subjects->pluck('id');
                        $centre->subjects()->syncWithoutDetaching($subjects);

                        foreach ($subjects as $subject) {
                            $subjectOrder = Subject::where('id', $subject)->first();
                            $centre->subjects()->updateExistingPivot($subject, array('order' => $subjectOrder->order));
                        }
                    }

                    $finalRes[] = "Success";
                    $finalResultAll[] = $finalRes;
                } else {
                    $errorCount++;
                    $finalRes[] = "Failed " . implode(',', $errors);
                    $finalResultAll[] = $finalRes;
                    $finalResultErrors[] = $finalRes;
                }
            }
        }

        $export = collect($finalResultAll);
        $errorExport = collect($finalResultErrors);

        $uniqid = Str::random();
        $errorFileName = 'centre_downlods/error/' . 'error_' . $uniqid . '.csv';
        $fileName = 'centre_downlods/' . $uniqid . '.csv';
        Excel::store(new CentreSuccessErrorExport($export), $fileName, 's3');
        Excel::store(new CentreSuccessErrorExport($errorExport), $errorFileName, 's3');
        $data['status'] = 1;
        $data['message'] = trans('admin.file_imported');
        if ($errorCount == 0) {
            $data['error_status'] = 0;
        } else {
            $data['error_status'] = 1;
            $data['error_file_name'] = generateTempUrl($errorFileName);
        }
        $data['uploaded_file_name'] = generateTempUrl($fileName);
        $this->data = $data;
    }


    /**
     * Set Approval Data
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [collection]
     */
    private function setApproval()
    {
        $approval = new Approval();
        $approval->type = Approval::TYPE_BULK;
        $approval->title = trans('admin.centre_bulk_approval_created');
        $approval->model = Approval::TYPE_CENTRE_MODEL;
        $approval->status = Approval::TYPE_NEEDS_APPROVAL;
        $approval->reference_id = $this->approvalConst;
        $approval->save();
        return $approval;
    }

    private function checkExcelFormat($row, $keyArray)
    {
        $i = 0;
        foreach ($row as $key => $value) {
            if ($key != $keyArray[$i]) {
                throw ValidationException::withMessages(
                    array("file" =>
                    "Invalid Excel Format," . ucfirst(str_replace('_', ' ', $keyArray[$i])) . " Missing")
                );
            }
            $i++;
        }
    }

    private function fetchdata($row)
    {
        $states = State::all();
        $districts = District::all();
        $centreTypes = CentreType::all();
        $organisations = Organisation::all();
        $partnershipTypes = PartnershipType::all();

        $centreDetails['centre_name'] = trim($row['centre_name']);
        $centreDetails['centre_id'] = (trim($row['centre_id']) == '') ? null : trim($row['centre_id']);
        $organisationDet = $this->getCollectionCaseInsensitiveString(
            $organisations,
            'name',
            trim($row['organisation_name'])
        )->first();
        $centreDetails['organisation_id'] = $organisationDet->id ?? "";
        $centreTypeDetails = $this->getCollectionCaseInsensitiveString(
            $centreTypes,
            'name',
            trim($row['centre_type'])
        )->first();
        $centreDetails['centre_type_id'] = $centreTypeDetails->id ?? "";
        $workingMode = array_search(trim($row['working_mode']), config('staticcontent.workingMode'));

        if ($workingMode !== false) {
            $centreDetails['working_mode'] = $workingMode;
        } else {
            $centreDetails['working_mode'] = "";
        }
        $centreDetails['location'] = trim($row['location']) ?? null;
        $centreDetails['email'] = trim($row['email']) ?? null;
        $centreDetails['mobile'] = trim($row['mobile']) ?? null;
        $centreDetails['website'] = $row['website'] ?? null;
        $centreDetails['address'] = $row['address'] ?? null;

        $stateDetails = $this->getCollectionCaseInsensitiveString(
            $states,
            'name',
            trim($row['state'])
        )->first();
        $centreDetails['state_id'] = $stateDetails->id ?? null;
        $districtDetails = $this->getCollectionCaseInsensitiveString(
            $districts,
            'name',
            trim($row['district'])
        )->first();
        $centreDetails['district_id'] = $districtDetails->id ?? null;
        $centreDetails['city'] = $row['city'] ?? null;
        $partnershipDetails = $this->getCollectionCaseInsensitiveString(
            $partnershipTypes,
            'name',
            trim($row['type_of_partnership'])
        )->first();
        $centreDetails['partnership_type_id'] = $partnershipDetails->id ?? "";
        $centreDetails['target_students'] = $row['target_students'] ?? 0;
        $centreDetails['target_trainers'] = $row['target_trainers'] ?? 0;
        if ($row['status'] == "Active") {
            $centreDetails['status'] = 1;
        } elseif ($row['status'] == "Inactive") {
            $centreDetails['status'] = 0;
        } else {
            $centreDetails['status'] = null;
        }
        $centreDetails['city'] = $row['city'] ?? null;
        return $centreDetails;
    }
    private function getCollectionCaseInsensitiveString($collection, $attribute, $value)
    {
        $collection = $collection->filter(function ($item) use ($attribute, $value) {
            return strtolower($item[$attribute]) == strtolower($value);
        });
        return $collection;
    }
    private function getProject($projects)
    {
        $projectError = [];
        $projectIdVal = [];
        $projectDetails = [];
        $projectDetails[0]['project_errors'] = [];
        $projectsArray = explode(",", $projects);
        if ($projectsArray) {
            foreach ($projectsArray as $projectName) {
                $projectDet = Project::where('name', $projectName)->first();
                if (!$projectDet) {
                    $projectError[] = $projectName;
                } else {
                    $projectIdVal[] = $projectDet->id;
                }
            }
            if (!empty($projectError)) {
                $projectDetails[0]['project_errors'] = "The following Projects " .
                    implode(",", $projectError) . " not in system";
            }
        }
        $projectDetails[1]['project_ids'] = $projectIdVal;
        return $projectDetails;
    }
    private function validateFields($id, $centreDetails, $errors)
    {
        $idCount = 0;
        if ($id) {
            $centre = Centre::where('id', $id)->first();
            if (!$centre) {
                $errors[] = trans('admin.invalid_id');
            }
            $authUser = auth()->user();
            $centre['id'] = $id;
            $centre['organisation_id'] = $centreDetails['organisation_id'];
            if (!policy($centre)->update($authUser, $centre)) {
                $errors[] = trans('admin.user_permission');
            }
            $nameCount = Centre::where('name', $centreDetails['centre_name'])->where('id', '!=', $id)->get()->count();
            if (!auth()->user()->can('centre.update')) {
                $errors[] = trans('admin.centre_no_update_permission');
            }
            if(trim($centreDetails['centre_id']) != '' && $centreDetails['centre_id'] != null) {
                $idCount = Centre::where('center_id', $centreDetails['centre_id'])->where('id', '!=', $id)->get()->count();
            }

        } else {
            $nameCount = Centre::where('name', $centreDetails['centre_name'])->get()->count();
            if (!auth()->user()->can('centre.create')) {
                $errors[] = trans('admin.centre_no_create_permission');
            }

            if (trim($centreDetails['centre_id']) != '' && $centreDetails['centre_id'] != null) {
                $idCount = Centre::where('center_id', $centreDetails['centre_id'])->get()->count();
            }
        }
        if ($nameCount > 0) {
            $errors[] = trans('admin.centre_exist');
        }

        if ($idCount > 0) {
            $errors[] = trans('admin.unique_centre_id');
        }

        if ($centreDetails['centre_name'] == "") {
            $errors[] = trans('admin.centre_name_missing');
        }


        if (trim($centreDetails['centre_id']) != '' && $centreDetails['centre_id'] != null) {

            if ($centreDetails['centre_id'] != "" && !is_numeric($centreDetails['centre_id'])) {
                $errors[] = trans('admin.invalid_centre_id');
            }
            
            if ($centreDetails['centre_id'] < 10000000000 || $centreDetails['centre_id'] > 99999999999) {
                $errors[] = trans('admin.invalid_centre_id_range');
            }

        }
        if ($centreDetails['organisation_id'] == "") {
            $errors[] = trans('admin.invalid_organisation');
        }
        if ($centreDetails['centre_type_id'] == "") {
            $errors[] = trans('admin.invalid_centre_type');
        }
        if ($centreDetails['working_mode'] == "") {
            $errors[] = trans('admin.invalid_working_mode');
        }
        if ($centreDetails['status'] == "") {
            $errors[] = trans('admin.invalid_status');
        }
        if ($centreDetails['email'] != "") {
            if (!preg_match('/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/', $centreDetails['email'])) {
                $errors[] = trans('admin.invalid_email');
            }
        }
        if ($centreDetails['mobile'] != "") {
            if (!is_numeric($centreDetails['mobile']) || strlen($centreDetails['mobile']) != 10) {
                $errors[] = trans('admin.invalid_mobile');
            }
        }
        if ($centreDetails['state_id'] == "") {
            $errors[] = trans('admin.state_missing');
        }
        if ($centreDetails['district_id'] == "") {
            $errors[] = trans('admin.district_missing');
        }
        if ($centreDetails['partnership_type_id'] == "") {
            $errors[] = trans('admin.partnership_type_missing');
        }
        if (!is_numeric($centreDetails['target_students']) || (strlen($centreDetails['target_students']) > 5)) {
            $errors[] = trans('admin.invalid_target_students');
        }
        if (!is_numeric($centreDetails['target_trainers']) || (strlen($centreDetails['target_trainers']) > 5)) {
            $errors[] = trans('admin.invalid_target_trainers');
        }
        if ($centreDetails['website'] != "") {
            if (!preg_match('/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/', $centreDetails['website'])) {
                $errors[] = trans('admin.invalid_website');
            }
        }
        return $errors;
    }

    private function setCentre($centre, $centreDetails)
    {
        $centre->name = $centreDetails['centre_name'];
        $centre->organisation_id = $centreDetails['organisation_id'];
        $centre->center_id = $centreDetails['centre_id'];
        $centre->centre_type_id = $centreDetails['centre_type_id'];
        $centre->working_mode = $centreDetails['working_mode'];
        $centre->location = $centreDetails['location'];
        $centre->email = $centreDetails['email'];
        $centre->mobile = $centreDetails['mobile'];
        $centre->website = $centreDetails['website'];
        $centre->address = $centreDetails['address'];
        $centre->state_id = $centreDetails['state_id'];
        $centre->district_id = $centreDetails['district_id'];
        $centre->city = $centreDetails['city'] ?? "";
        $centre->status = $centreDetails['status'];
        $centre->activation_code = Str::random(15);
        $centre->tenant_id = getTenant();
        $centre->target_students = $centreDetails['target_students'];
        $centre->target_trainers = $centreDetails['target_trainers'];
        $centre->partnership_type_id = $centreDetails['partnership_type_id'];
        return $centre;
    }
}
