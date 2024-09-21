<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentSuccessErrorExport implements FromCollection, WithHeadings, ShouldAutoSize
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
            trans('admin.gender'),
            trans('admin.dob'),
            trans('admin.email'),
            trans('admin.mobile'),
            trans('admin.trade_course'),
            trans('admin.educational_qualification'),
            trans('admin.marital_status'),
            trans('admin.work_experience_years'),
            trans('admin.last_month_salary'),
            trans('admin.guardian_name'),
            trans('admin.guardian_phone'),
            trans('admin.annual_family_income'), 
            trans('admin.guardian_occupation'),
            trans('admin.contactability'),
            trans('admin.not_contactable_reason'),
            trans('admin.interview1_company_name'),
            trans('admin.interview1_date'),
            trans('admin.interview1_result'),
            trans('admin.interview2_company_name'),
            trans('admin.interview2_date'),
            trans('admin.interview2_result'),
            trans('admin.interview3_company_name'),
            trans('admin.interview3_date'),
            trans('admin.interview3_result'),
            trans('admin.placed'),
            trans('admin.month_of_joining'),
            trans('admin.date_of_updation'),
            trans('admin.remarks'),
            trans('admin.batch_completion_status'),
            trans('admin.company_name'),
            trans('admin.designation'),
            trans('admin.location'),
            trans('admin.sector'),
            trans('admin.offerletter_recieved'),
            trans('admin.offerletter_type'),
            trans('admin.income_month'),
            trans('admin.sector_self_employed'),
            trans('admin.income_month_self_employed'),
            trans('admin.course'),
            trans('admin.location_higher_study'),
            trans('admin.reason'),
            trans('admin.status_after_3month'),
            trans('admin.company_after_3month'),
            trans('admin.designation_after_3month'),
            trans('admin.location_after_3month'),
            trans('admin.sector_after_3month'),
            trans('admin.offerletter_after_3month'),
            trans('admin.offerletter_type_after_3month'),
            trans('admin.income_month_after_3month'),
            trans('admin.sector_self_employed_after_3month'),
            trans('admin.income_self_employed_after_3month'),
            trans('admin.course_after_3month'),
            trans('admin.location_higherstudy_after_3month'),
            trans('admin.reason_after_3month'),
            trans('admin.updated_email'),
            trans('admin.updated_mobile'),
            trans('admin.status_after_6month'),
            trans('admin.company_after_6month'),
            trans('admin.designation_after_6month'),
            trans('admin.location_after_6month'),
            trans('admin.sector_after_6month'),
            trans('admin.offerletter_after_6month'),
            trans('admin.offerletter_type_after_6month'),
            trans('admin.income_month_after_6month'),
            trans('admin.sector_self_employed_after_6month'),
            trans('admin.income_self_employed_after_6month'),
            trans('admin.course_after_6month'),
            trans('admin.location_higherstudy_after_6month'),
            trans('admin.reason_after_6month'),
        ];
    }
}
