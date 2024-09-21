<?php

namespace App\Exports;

use App\Models\Centre;
use App\Models\PlacementStatus;
use App\Models\PlacementType;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentBatchExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    protected $filterData;

    public function __construct($filterData)
    {
        $this->filterData = $filterData;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {

        $students = DB::table('student_report_view')
        ->selectRaw("id,name,gender,date_of_birth,email,mobile,centre_name,trade_name, qualification, marital_status,experience,last_month_salary, guardian_name,guardian_mobile, guardian_income,guardian_occupation,batch_name, start_date, end_date, organisation_name,created_at, contactability, not_contactable_reason,interview1_company_name, interview1_date,interview1_result, interview2_company_name,interview2_date, interview2_result,interview3_company_name, interview3_date,interview3_result,placed, month_of_joining,date_of_updation,remarks,group_concat(batch_completion_status,'') as batch_completion_status,group_concat(company1,'') as company1,group_concat(designation1,'') as designation1,group_concat(district_id1,'') as district_id1,group_concat(sector1,'') as sector1,group_concat(offerletter_status1,'') as offerletter_status1,group_concat(offerletter_type1,'') as offerletter_type1,group_concat(salary1,'') as salary1,group_concat(sector_selfemp1,'') as sector_selfemp1,group_concat(salary_selfemp1,'') as salary_selfemp1,group_concat(placement_course_id1,'') as placement_course_id1,group_concat(location_id1,'') as location_id1,group_concat(reason1,'') as reason1,group_concat(status_after_3_months,'') as status_after_3_months,group_concat(company2,'') as company2,group_concat(designation2,'') as designation2,group_concat(district_id2,'') as district_id2,group_concat(sector2,'') as sector2,group_concat(offerletter_status2,'') as offerletter_status2,group_concat(offerletter_type2,'') as offerletter_type2,group_concat(salary2,'') as salary2,group_concat(sector_selfemp2,'') as sector_selfemp2,group_concat(salary_selfemp2,'') as salary_selfemp2,group_concat(placement_course_id2,'') as placement_course_id2,group_concat(location_id2,'') as location_id2,group_concat(reason2,'') as reason2,group_concat(status_after_6_months,'') as status_after_6_months,group_concat(company3,'') as company3,group_concat(designation3,'') as designation3,group_concat(district_id3,'') as district_id3,group_concat(sector3,'') as sector3,group_concat(offerletter_status3,'') as offerletter_status3,group_concat(offerletter_type3,'') as offerletter_type3,group_concat(salary3,'') as salary3,group_concat(sector_selfemp3,'') as sector_selfemp3,group_concat(salary_selfemp3,'') as salary_selfemp3,group_concat(placement_course_id3,'') as placement_course_id3,group_concat(location_id3,'') as location_id3,group_concat(reason3,'') as reason3")
            ->when($this->filterData['centre.administrator'], function ($query) {
                return $query->where('centre_id', auth()->user()->centre_id);
            })
            ->when($this->filterData['organisation.administrator'], function ($query) {
                return $query->where('organisation_id', auth()->user()->organisation_id);
            })
            ->when($this->filterData['program.administrator'], function ($query2) {
                return $query2->whereIn('organisation_id', function ($query) {
                    return $query->select('organisation_id')
                        ->from('organisation_program')->where('program_id', auth()->user()->program_id);
                });
            })
            ->when($this->filterData['project.administrator'], function ($query3) {
                return $query3->whereIn('centre_id', function ($query) {
                    return $query->select('centre_id')
                        ->from('centre_project')->where('project_id', auth()->user()->project_id);
                });
            })
            ->when($this->filterData['batch_id'], function ($query1) {
                return $query1->where('batch_id', '=', $this->filterData['batch_id']);
            })
            ->whereIn('user_type', array(User::TYPE_STUDENT, User::TYPE_ALUMNI))
            ->groupByRaw('id,name,gender,email,mobile,centre_name,trade_name, qualification, marital_status,experience,last_month_salary, guardian_name,guardian_mobile, guardian_income,guardian_occupation,batch_name, start_date, end_date, organisation_name,created_at, contactability, not_contactable_reason,interview1_company_name, interview1_date,interview1_result, interview2_company_name,interview2_date, interview2_result,interview3_company_name, interview3_date,interview3_result,placed, month_of_joining,date_of_updation,remarks,date_of_birth')
            ->get();
        return $students;
    }

    public function map($student): array
    {

        $values = [];
        $values = [
            $student->id,
            $student->name,
            $student->gender,
            $student->date_of_birth ?? "",
            isset($student->date_of_birth) ? Carbon::parse($student->date_of_birth)->age ?? null : null,
            $student->email ?? "",
            $student->mobile ?? "",
            $student->trade_name ?? "",
            $student->centre_name ?? "",
            $student->qualification ?? "",
            config('staticcontent.maritalStatus.' . $student->marital_status) ?? "",
            $student->experience ?? "",
            $student->last_month_salary ?? "",
            $student->guardian_name ?? "",
            $student->guardian_mobile ?? "",
            $student->guardian_income ?? "",
            $student->guardian_occupation ?? "",
            isset($student->start_date) ? date('Y-m-d', strtotime($student->start_date)) : null,
            isset($student->end_date) ? date('Y-m-d', strtotime($student->end_date)) : null,
            $student->batch_name ?? "",
            $student->organisation_name ?? "",
            $student->created_at ?? "",
            $student->contactability == 1 ? 'yes' : 'no',
            $student->not_contactable_reason ?? "",
            $student->interview1_company_name ?? "",
            isset($student->interview1_date) ?
                (date('d-m-Y', strtotime($student->interview1_date)) ?: null) : null,
            config('staticcontent.interview_result.' . $student->interview1_result) ?? "",
            $student->interview2_company_name ?? "",
            isset($student->interview2_date) ?
                (date('d-m-Y', strtotime($student->interview2_date)) ?: null) : null,
            config('staticcontent.interview_result.' . $student->interview2_result) ?? "",
            $student->interview3_company_name ?? "",
            isset($student->interview3_date) ?
                (date('d-m-Y', strtotime($student->interview3_date)) ?: null) : null,
            config('staticcontent.interview_result.' . $student->interview3_result) ?? "",
            $student->placed == 1 ? 'yes' : 'no',
            config('staticcontent.month_of_joining.' . $student->month_of_joining) ?? "",
            $student->date_of_updation ?? "",
            $student->remarks ?? ""
        ];
        $values[] = trim($student->batch_completion_status, ',');
        $values[] = trim($student->company1, ',');
        $values[] = trim($student->designation1, ',');
        $values[] = trim($student->district_id1, ',');
        $values[] = trim($student->sector1, ',');
        $values[] = trim($student->offerletter_status1, ',');
        $values[] = trim($student->offerletter_type1, ',');
        $values[] = trim($student->salary1, ',');
        $values[] = trim($student->sector_selfemp1, ',');
        $values[] = trim($student->salary_selfemp1, ',');
        $values[] = trim($student->placement_course_id1, ',');
        $values[] = trim($student->location_id1, ',');
        $values[] = trim($student->reason1, ',');
        $values[] = trim($student->status_after_3_months, ',');
        $values[] = trim($student->company2, ',');
        $values[] = trim($student->designation2, ',');
        $values[] = trim($student->district_id2, ',');
        $values[] = trim($student->sector2, ',');
        $values[] = trim($student->offerletter_status2, ',');
        $values[] = trim($student->offerletter_type2, ',');
        $values[] = trim($student->salary2, ',');
        $values[] = trim($student->sector_selfemp2, ',');
        $values[] = trim($student->salary_selfemp2, ',');
        $values[] = trim($student->placement_course_id2, ',');
        $values[] = trim($student->location_id2, ',');
        $values[] = trim($student->reason2, ',');
        $values[] = trim($student->status_after_6_months, ',');
        $values[] = $student->updated_email ?? "";
        $values[] = $student->updated_mobile ?? "";
        $values[] = trim($student->company3, ',');
        $values[] = trim($student->designation3, ',');
        $values[] = trim($student->district_id3, ',');
        $values[] = trim($student->offerletter_status3, ',');
        $values[] = trim($student->offerletter_type3, ',');
        $values[] = trim($student->salary3, ',');
        $values[] = trim($student->sector_selfemp3, ',');
        $values[] = trim($student->salary_selfemp3, ',');
        $values[] = trim($student->placement_course_id3, ',');
        $values[] = trim($student->location_id3, ',');
        $values[] = trim($student->reason3, ',');

        return $values;
    }

    public function headings(): array
    {
        return [
            trans('admin.id'),
            trans('admin.name'),
            trans('admin.gender'),
            trans('admin.dob'),
            trans('admin.age'),
            trans('admin.email'),
            trans('admin.mobile'),
            trans('admin.trade_course'),
            trans('admin.training_centre'),
            trans('admin.educational_qualification'),
            trans('admin.marital_status'),
            trans('admin.work_experience_years'),
            trans('admin.last_month_salary'),
            trans('admin.guardian_name'),
            trans('admin.guardian_phone'),
            trans('admin.annual_family_income'),
            trans('admin.guardian_occupation'),
            trans('admin.batch_start_date'),
            trans('admin.batch_end_date'),
            trans('admin.batch_name'),
            trans('admin.training_organisation'),
            trans('admin.stud_creation_date'),
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
            trans('admin.location') . '(' . trans('admin.district') . ')',
            trans('admin.sector') . '(' . trans('admin.if_employed') . ')',
            trans('admin.offerletter_recieved') . '(' . trans('admin.if_employed') . ')',
            trans('admin.offerletter_type') . '(' . trans('admin.if_employed') . ')',
            trans('admin.gross_income_month') . '(' . trans('admin.if_employed') . ')',
            trans('admin.sector') . '(' . trans('admin.if_self_employed') . ')',
            trans('admin.gross_income_month') . '(' . trans('admin.if_self_employed') . ')',
            trans('admin.course') . '(' . trans('admin.if_higher_studies') . ')',
            trans('admin.location') . '(' . trans('admin.if_higher_studies') . ')',
            trans('admin.reason') . '(' . trans('admin.if_dropout') . ')',
            trans('admin.status_after_3month'),
            trans('admin.company_name') . '(' . trans('admin.if_employed') . ')',
            trans('admin.designation') . '(' . trans('admin.if_employed') . ')',
            trans('admin.location') . '(' . trans('admin.if_employed') . ')',
            trans('admin.sector') . '(' . trans('admin.if_employed') . ')',
            trans('admin.offerletter_recieved') . '(' . trans('admin.if_employed') . ')',
            trans('admin.offerletter_type') . '(' . trans('admin.if_employed') . ')',
            trans('admin.gross_income_month') . '(' . trans('admin.if_employed') . ')',
            trans('admin.sector') . '(' . trans('admin.if_self_employed') . ')',
            trans('admin.gross_income_month') . '(' . trans('admin.if_self_employed') . ')',
            trans('admin.course') . '(' . trans('admin.if_higher_studies') . ')',
            trans('admin.location') . '(' . trans('admin.if_higher_studies') . ')',
            trans('admin.reason') . '(' . trans('admin.if_not_working') . ')',
            trans('admin.email') . '(' . trans('admin.updated') . ')',
            trans('admin.mobile') . '(' . trans('admin.updated') . ')',
            trans('admin.status_after_6month'),
            trans('admin.company_name') . '(' . trans('admin.if_employed') . ')',
            trans('admin.designation') . '(' . trans('admin.if_employed') . ')',
            trans('admin.location') . '(' . trans('admin.if_employed') . ')',
            trans('admin.sector') . '(' . trans('admin.if_employed') . ')',
            trans('admin.offerletter_recieved') . '(' . trans('admin.if_employed') . ')',
            trans('admin.offerletter_type') . '(' . trans('admin.if_employed') . ')',
            trans('admin.gross_income_month') . '(' . trans('admin.if_employed') . ')',
            trans('admin.sector') . '(' . trans('admin.if_self_employed') . ')',
            trans('admin.gross_income_month') . '(' . trans('admin.if_self_employed') . ')',
            trans('admin.course') . '(' . trans('admin.if_higher_studies') . ')',
            trans('admin.location') . '(' . trans('admin.if_higher_studies') . ')',
            trans('admin.reason') . '(' . trans('admin.if_not_working') . ')',
        ];
    }

    public function getPlacementInit($placementInit, $placementInitStatus, $values)
    {
        $values[] = $placementInit && ($placementInitStatus == PlacementStatus::EMPLOYED_STATUS ||
            $placementInitStatus == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS) ? $placementInit->company : '';
        $values[] = $placementInit && ($placementInitStatus == PlacementStatus::EMPLOYED_STATUS
            || $placementInitStatus == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? $placementInit->designation : '';
        $values[] = $placementInit && ($placementInitStatus == PlacementStatus::EMPLOYED_STATUS
            || $placementInitStatus == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? isset($placementInit->location->name) ? $placementInit->location->name : null : '';
        $values[] = $placementInit && ($placementInitStatus == PlacementStatus::EMPLOYED_STATUS
            || $placementInitStatus == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? isset($placementInit->sector->name) ? $placementInit->sector->name : null : '';
        $values[] = $placementInit && ($placementInitStatus == PlacementStatus::EMPLOYED_STATUS
            || $placementInitStatus == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? isset($placementInit->offerletterStatus->name) ? $placementInit->offerletterStatus->name : null : '';
        $values[] = $placementInit && ($placementInitStatus == PlacementStatus::EMPLOYED_STATUS
            || $placementInitStatus == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? isset($placementInit->offerletterType->name) ? $placementInit->offerletterType->name : null : '';
        $values[] = $placementInit && ($placementInitStatus == PlacementStatus::EMPLOYED_STATUS
            || $placementInitStatus == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? $placementInit->salary : '';
        $values[] = $placementInit && $placementInitStatus == PlacementStatus::SELF_EMPLOYED_STATUS
            ? isset($placementInit->sector->name) ? $placementInit->sector->name : null : '';
        $values[] = $placementInit && $placementInitStatus == PlacementStatus::SELF_EMPLOYED_STATUS
            ? $placementInit->salary : '';
        $values[] = $placementInit && $placementInitStatus == PlacementStatus::HIGHER_STUDIES_STATUS
            ? isset($placementInit->placementCourse->name) ? $placementInit->placementCourse->name : null : '';
        $values[] = $placementInit && $placementInitStatus == PlacementStatus::HIGHER_STUDIES_STATUS
            ? isset($placementInit->location->name) ? $placementInit->location->name : null : '';
        $values[] = $placementInit && $placementInitStatus == PlacementStatus::DROPOUT_STATUS
            ? $placementInit->reason : '';
        return $values;
    }

    public function getPlacementAfter3($placementAfter3, $placementAfter3Status, $values)
    {
        $values[] = $placementAfter3 && ($placementAfter3Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter3Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? $placementAfter3->company : '';
        $values[] = $placementAfter3 && ($placementAfter3Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter3Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? $placementAfter3->designation : '';
        $values[] = $placementAfter3 && ($placementAfter3Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter3Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? isset($placementAfter3->location->name) ? $placementAfter3->location->name : null : '';
        $values[] = $placementAfter3 && ($placementAfter3Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter3Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? isset($placementAfter3->sector->name) ? $placementAfter3->sector->name : null : '';
        $values[] = $placementAfter3 && ($placementAfter3Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter3Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? isset($placementAfter3->offerletterStatus->name) ? $placementAfter3->offerletterStatus->name : null : '';
        $values[] = $placementAfter3 && ($placementAfter3Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter3Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? isset($placementAfter3->offerletterType->name) ? $placementAfter3->offerletterType->name : null : '';
        $values[] = $placementAfter3 && ($placementAfter3Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter3Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? $placementAfter3->salary : '';
        $values[] = $placementAfter3 && $placementAfter3Status == PlacementStatus::SELF_EMPLOYED_STATUS
            ? isset($placementAfter3->sector->name) ? $placementAfter3->sector->name : null : '';
        $values[] = $placementAfter3 && $placementAfter3Status == PlacementStatus::SELF_EMPLOYED_STATUS
            ? $placementAfter3->salary : '';
        $values[] = $placementAfter3 && $placementAfter3Status == PlacementStatus::HIGHER_STUDIES_STATUS
            ? isset($placementAfter3->placementCourse->name) ? $placementAfter3->placementCourse->name : null : '';
        $values[] = $placementAfter3 && $placementAfter3Status == PlacementStatus::HIGHER_STUDIES_STATUS
            ? isset($placementAfter3->location->name) ? $placementAfter3->location->name : null : '';
        $values[] = $placementAfter3 && $placementAfter3Status == PlacementStatus::NOT_WORKING_STATUS
            ? $placementAfter3->reason : '';
        return $values;
    }

    public function getPlacementAfter6($placementAfter6, $placementAfter6Status, $values)
    {
        $values[] = $placementAfter6 && ($placementAfter6Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter6Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? $placementAfter6->company : '';
        $values[] = $placementAfter6 && ($placementAfter6Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter6Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? $placementAfter6->designation : '';
        $values[] = $placementAfter6 && ($placementAfter6Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter6Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? isset($placementAfter6->location->name) ? $placementAfter6->location->name : null : '';
        $values[] = $placementAfter6 && ($placementAfter6Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter6Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? isset($placementAfter6->sector->name) ? $placementAfter6->sector->name : null : '';
        $values[] = $placementAfter6 && ($placementAfter6Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter6Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? isset($placementAfter6->offerletterStatus->name) ? $placementAfter6->offerletterStatus->name : null : '';
        $values[] = $placementAfter6 && ($placementAfter6Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter6Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? isset($placementAfter6->offerletterType->name) ? $placementAfter6->offerletterType->name : null : '';
        $values[] = $placementAfter6 && ($placementAfter6Status == PlacementStatus::EMPLOYED_STATUS
            || $placementAfter6Status == PlacementStatus::APPRENTICESHIP_INTERNSHIP_STATUS)
            ? $placementAfter6->salary : '';
        $values[] = $placementAfter6 && $placementAfter6Status == PlacementStatus::SELF_EMPLOYED_STATUS
            ? isset($placementAfter6->sector->name) ? $placementAfter6->sector->name : null : '';
        $values[] = $placementAfter6 && $placementAfter6Status == PlacementStatus::SELF_EMPLOYED_STATUS
            ? $placementAfter6->salary : '';
        $values[] = $placementAfter6 && $placementAfter6Status == PlacementStatus::HIGHER_STUDIES_STATUS
            ? isset($placementAfter6->placementCourse->name) ? $placementAfter6->placementCourse->name : null : '';
        $values[] = $placementAfter6 && $placementAfter6Status == PlacementStatus::HIGHER_STUDIES_STATUS
            ? isset($placementAfter6->location->name) ? $placementAfter6->location->name : null : '';
        $values[] = $placementAfter6 && $placementAfter6Status == PlacementStatus::NOT_WORKING_STATUS
            ? $placementAfter6->reason : '';
        return $values;
    }
}
