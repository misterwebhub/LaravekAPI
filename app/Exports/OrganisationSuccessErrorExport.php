<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OrganisationSuccessErrorExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public $organisation;

    public function __construct($organisation)
    {
        $this->organisation = $organisation;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->organisation;
    }

    public function headings(): array
    {
        return [
            trans('admin.id'),
            trans('admin.organisation_name'),
            trans('admin.email'),
            trans('admin.mobile'),
            trans('admin.website'),
            trans('admin.address'),
            trans('admin.pincode'),
            trans('admin.state'),
            trans('admin.district'),
            trans('admin.city'),
            trans('admin.program'),
        ];
    }
}
