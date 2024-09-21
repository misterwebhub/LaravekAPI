<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CentreSuccessErrorExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public $centre;

    public function __construct($centre)
    {
        $this->centre = $centre;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->centre;
    }

    public function headings(): array
    {
        return [
            trans('admin.id'),
            trans('admin.centre_name'),
            trans('admin.centre_id'),
            trans('admin.organisation_name'),
            trans('admin.projects'),
            trans('admin.centre_type'),
            trans('admin.partnership_type'),
            trans('admin.target_students'),
            trans('admin.target_trainers'),
            trans('admin.working_mode'),
            trans('admin.location'),
            trans('admin.email'),
            trans('admin.mobile'),
            trans('admin.website'),
            trans('admin.address'),
            trans('admin.state'),
            trans('admin.district'),
            trans('admin.city'),
            trans('admin.status'),
        ];
    }
}
