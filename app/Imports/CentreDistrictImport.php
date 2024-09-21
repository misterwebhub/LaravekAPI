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
use App\Models\Approval;
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
class CentreDistrictImport implements ToCollection, WithHeadingRow
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
            0 => 'centre_name',
            1 => 'organisation_name',
            2 => 'projects',
            3 => 'centre_type',
            4 => 'type_of_partnership',
            5 => 'target_students',
            6 => 'target_trainers',
            7 => 'working_mode',
            8 => 'location',
            9 => 'email',
            10 => 'mobile',
            11 => 'website',
            12 => 'address',
            13 => 'state',
            14 => 'district',
            15 => 'city',
            16 => 'status',
            17 => 'location_type'
        );
        foreach ($collection->chunk(10) as $chunk) {

            foreach ($chunk as $row) {
                $finalRes = [];
                $errors = [];
                $this->checkExcelFormat($row, $keyArray);
                $centreDetails = $this->fetchdata($row);

                if ($row['centre_name']) {
                    $centre = Centre::where('name', $row['centre_name'])
                        ->where('organisation_id', $centreDetails['organisation_id'])
                        ->where('state_id', $centreDetails['state_id'])
                        ->where('active', Centre::ACTIVE_STATUS)
                        ->first();
                    if ($centre) {
                        if ($centre->id && ($centreDetails['district_id'] != null)) {
                             $centre->district_id = $centreDetails['district_id'];
                            $centre->update();
                        }
                    }
                }
            }
        }
        $data['status'] = 1;
        $data['message'] = trans('admin.file_imported');
        $this->data = $data;
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
        $organisations = Organisation::all();

        $centreDetails['centre_name'] = trim($row['centre_name']);
        $organisationDet = $this->getCollectionCaseInsensitiveString(
            $organisations,
            'name',
            trim($row['organisation_name'])
        )->first();
        $centreDetails['organisation_id'] = $organisationDet->id ?? "";


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

        return $centreDetails;
    }
    private function getCollectionCaseInsensitiveString($collection, $attribute, $value)
    {
        $collection = $collection->filter(function ($item) use ($attribute, $value) {
            return strtolower($item[$attribute]) == strtolower($value);
        });
        return $collection;
    }
}
