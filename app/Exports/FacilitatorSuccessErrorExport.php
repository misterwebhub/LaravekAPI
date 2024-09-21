<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FacilitatorSuccessErrorExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public $facilitator;

    public function __construct($facilitator)
    {
        $this->facilitator = $facilitator;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->facilitator;
    }

    public function headings(): array
    {
        return [
            trans('admin.id'),
            trans('admin.facilitator_name'),
            trans('admin.organisation_name'),
            trans('admin.centre_name'),
            trans('admin.gender'),
            trans('admin.designation'),
            trans('admin.email'),
            trans('admin.dob'),
            trans('admin.phone'),
            trans('admin.highest_qualification'),
            trans('admin.Work_experience'),
            trans('admin.is_super_facilitator'),
            trans('admin.is_master_trainer'),
        ];
    }
}
