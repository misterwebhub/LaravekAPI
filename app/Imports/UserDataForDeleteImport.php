<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;

use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Class OrganisationDataImport
 * @package App\Imports
 */
class UserDataForDeleteImport implements ToCollection, WithHeadingRow
{
    public $data;


    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {


        $keyArray = array(
            0 => 'created_at',
            1 => 'user_id',
            2 => 'type',
            3 => 'Org Name',
            4 => 'Centre Name',
            5 => 'Project Name',
            6 => 'State Name',
            7 => 'Centre Name',

        );
        foreach ($collection->chunk(10) as $chunk) {

            foreach ($chunk as $row) {


                if ($row['user_id']) {
                    $user = User::where('id', $row['user_id'])
                        ->first();
                    if ($user) {
                        $user->studentDetail()->delete();
                        $user->delete();
                    }
                }
            }
        }
        $data['status'] = 1;
        $data['message'] = trans('admin.file_imported');
        $this->data = $data;
    }
}
