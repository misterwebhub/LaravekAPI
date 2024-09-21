<?php

namespace App\Imports;

use App\Exports\OrganisationSuccessErrorExport;
use App\Models\District;
use App\Models\Organisation;
use App\Models\Program;
use App\Models\State;
use App\Models\Approval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

/**
 * Class OrganisationDataImport
 * @package App\Imports
 */
class OrganisationImport implements ToCollection, WithHeadingRow
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
            1 => 'organisation_name',
            2 => 'email',
            3 => 'mobile',
            4 => 'website',
            5 => 'address',
            6 => 'pincode',
            7 => 'state',
            8 => 'district',
            9 => 'city',
            10 => 'program',
        );
        $errorCount = 0;
        foreach ($collection->chunk(10) as $chunk) {
            foreach ($chunk as $row) {
                $finalRes = [];
                $errors = [];

                $this->checkExcelFormat($row, $keyArray);
                $orgDetails = $this->fetchdata($row);

                $programs = $row['program'];
                foreach ($row as $value) {
                    $finalRes[] = $value;
                }
                $programDetails = $this->addProgram($programs);

                if ($row['id']) {
                    $organisation = Organisation::where('id', $row['id'])->first();
                    $errors = $this->validateFields($row['id'], $orgDetails);
                } else {
                    $organisation = new Organisation();
                    $errors = $this->validateFields("", $orgDetails);
                }
                if (empty($errors)) {
                    $organisation = $this->setOrganisation($organisation, $orgDetails);
                    if ($row['id']) {
                        $organisation->update();
                    } else {
                        if (auth()->user()->hasPermissionTo('activity.needs.approval')) {
                            $approvalReferenceId = Approval::where('reference_id', $this->approvalConst)->first();
                            if (empty($approvalReferenceId)) {
                                $approvalReferenceId = $this->setApproval();
                            }
                            $organisation->is_approved = Organisation::TYPE_NOT_APPROVED;
                            $organisation->approval_group = $approvalReferenceId->reference_id;
                        }
                        $organisation->save();
                    }
                    DB::commit();
                    $organisation->program()->sync($programDetails);
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
        $errorFileName = 'organisation_downlods/error/' . 'error_' . $uniqid . '.csv';
        $fileName = 'organisation_downlods/' . $uniqid . '.csv';
        Excel::store(new OrganisationSuccessErrorExport($export), $fileName, 's3');
        Excel::store(new OrganisationSuccessErrorExport($errorExport), $errorFileName, 's3');
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
        $approval->title = trans('admin.organisation_bulk_approval_created');
        $approval->model = Approval::TYPE_ORGANISATION_MODEL;
        $approval->status = Approval::TYPE_NEEDS_APPROVAL;
        $approval->reference_id = $this->approvalConst;
        $approval->save();
        return $approval;
    }

    private function fetchdata($row)
    {
        $orgDetails['name'] = trim($row['organisation_name']);
        $orgDetails['email'] = trim($row['email']) ?? null;
        $orgDetails['mobile'] = trim($row['mobile']) ?? null;
        $orgDetails['website'] = $row['website'] ?? null;
        $orgDetails['address'] = $row['address'] ?? null;
        $orgDetails['pincode'] = $row['pincode'] ?? null;
        $states = State::all();
        $districts = District::all();
        $stateDetails = $this->getCollectionCaseInsensitiveString(
            $states,
            'name',
            trim($row['state'])
        )->first();
        $orgDetails['state_id'] = $stateDetails->id ?? $row['state'];
        $districtDetails = $this->getCollectionCaseInsensitiveString(
            $districts,
            'name',
            trim($row['district'])
        )->first();
        $orgDetails['district_id'] = $districtDetails->id ?? $row['district'];
        $orgDetails['city'] = $row['city'] ?? null;
        return $orgDetails;
    }

    private function getCollectionCaseInsensitiveString($collection, $attribute, $value)
    {
        $collection = $collection->filter(function ($item) use ($attribute, $value) {
            return strtolower($item[$attribute]) == strtolower($value);
        });
        return $collection;
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
    private function addProgram($programs)
    {
        $programArray = explode(',', $programs);
        foreach ($programArray as $programName) {
            $programCount = Program::where('name', $programName)->get()->count();
            if ($programCount == 0) {
                $program = new Program();
                $program->name = $programName;
                $program->tenant_id = getTenant();
                $program->save();
            }
        }

        return Program::whereIn('name', $programArray)->pluck('id');
    }
    private function validateFields($id, $orgDetails)
    {
        $errors = [];
        if ($id) {
            $organisation = Organisation::where('id', $id)->first();

            if (!$organisation) {
                $errors[] = trans('admin.invalid_id');
            }
            $authUser = auth()->user();
            $organisation['id'] = $id;
            if (!policy($organisation)->update($authUser, $organisation)) {
                $errors[] = trans('admin.user_permission');
            }
            $nameCount = Organisation::where('name', $orgDetails['name'])->where('id', '!=', $id)->get()->count();
            if (!auth()->user()->can('organisation.update')) {
                $errors[] = trans('admin.organisation_no_update_permission');
            }
        } else {
            $nameCount = Organisation::where('name', $orgDetails['name'])->get()->count();
            if (!auth()->user()->can('organisation.create')) {
                $errors[] = trans('admin.organisation_no_create_permission');
            }
        }
        if ($nameCount > 0) {
            $errors[] = trans('admin.organisation_exist');
        }
        if (trim($orgDetails['name']) == "") {
            $errors[] = trans('admin.organisation_name_missing');
        }
        if ($orgDetails['email'] != "") {
            if (!preg_match('/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/', $orgDetails['email'])) {
                $errors[] = trans('admin.invalid_email');
            }
        }
        if ($orgDetails['mobile'] != "") {
            if (!is_numeric($orgDetails['mobile']) || strlen($orgDetails['mobile']) != 10) {
                $errors[] = trans('admin.invalid_mobile');
            }
        }
        if ($orgDetails['website'] != "") {
            if (!preg_match("/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/", $orgDetails['website'])) {
                $errors[] = trans('admin.invalid_website');
            }
        }
        $errors = array_merge($errors, $this->addressValidation($orgDetails));
        return $errors;
    }
    private function addressValidation($orgDetails)
    {
        $addressErrors = [];
        if ($orgDetails['pincode'] != "") {
            if (!is_numeric($orgDetails['pincode']) || strlen($orgDetails['pincode']) != 6) {
                $addressErrors[] = trans('admin.invalid_picode');
            }
        }
        if ($orgDetails['state_id'] != "") {
            $state = State::where('id', $orgDetails['state_id'])->first();
            if (!$state) {
                $addressErrors[] = trans('admin.invalid_state');
            }
        }
        if ($orgDetails['district_id'] != "") {
            $district = District::where('id', $orgDetails['district_id'])->first();
            if (!$district) {
                $addressErrors[] = trans('admin.invalid_district');
            }
        }
        return $addressErrors;
    }

    private function setOrganisation($organisation, $orgDetails)
    {
        $organisation->name = $orgDetails['name'];
        $organisation->email = $orgDetails['email'];
        $organisation->mobile = $orgDetails['mobile'];
        $organisation->pincode = $orgDetails['pincode'];
        $organisation->website = $orgDetails['website'];
        $organisation->address = $orgDetails['address'];
        $organisation->state_id = $orgDetails['state_id'];
        $organisation->district_id = $orgDetails['district_id'];
        $organisation->city = $orgDetails['city'] ?? "";
        $organisation->tenant_id = getTenant();
        return $organisation;
    }
}
