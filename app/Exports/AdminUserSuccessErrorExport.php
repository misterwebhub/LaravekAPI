<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AdminUserSuccessErrorExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public $facilitator;

    public function __construct($student)
    {
        $this->student = $student;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->student;
    }

    public function headings(): array
    {
        return [
            trans('admin.id'),
            trans('admin.name'),
            trans('admin.email'),
            trans('admin.mobile'),
            trans('admin.role'),
            trans('admin.centre_org_program_project'),

        ];
    }
}
