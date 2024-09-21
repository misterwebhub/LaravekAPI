<?php

namespace App\Exports;

use App\Models\Centre;
use App\Models\PlacementStatus;
use App\Models\PlacementType;
use App\Models\User;
use App\Models\Phase;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\QueryBuilder\QueryBuilder;

class PhaseStudentExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    protected $phaseId;
    protected $phaseName;
    protected $startDate;
    protected $endDate;
    protected $projectName;
    protected $batchStatus;

    public function __construct($phaseId)
    {
        $this->phaseId = $phaseId;
        $phaseDet = $this->getPhaseDetails($phaseId);
        $this->projectName = "";
        $this->phaseName = $phaseDet->name;
        $this->startDate = $phaseDet->start_date;
        $this->endDate = $phaseDet->end_date;
        $this->batchStatus = "";
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $phaseId = $this->phaseId;
        $phase = Phase::find($phaseId);
        if ($phase->projects->toArray()) {
            $projectsList = $phase->projects->pluck('name')->toArray();
        } else {
            $projectsList = [];
        }
        $this->projectName = implode("", $projectsList);
        return QueryBuilder::for(User::class)
            ->with(['studentDetail', 'phaseUsers'])
            ->whereHas('phaseUsers', function ($q) use ($phaseId) {
                $q->where('phase_id', '=', $phaseId)
                ->whereNull('phase_users.deleted_at');
            })
            ->when(auth()->user()->hasPermissionTo('centre.administrator'), function ($query) {
                return $query->where('centre_id', auth()->user()->centre_id);
            })
            ->when(auth()->user()->hasPermissionTo('organisation.administrator'), function ($query) {
                return $query->where('organisation_id', auth()->user()->organisation_id);
            })
            ->when(auth()->user()->hasPermissionTo('program.administrator'), function ($query) {
                $organisationIds = auth()->user()->program->organisation->pluck('id')->toArray();
                $centreIds = Centre::whereIn('organisation_id', $organisationIds)->pluck('id')->toArray();
                return $query->whereIn('centre_id', $centreIds);
            })
            ->when(
                auth()->user()->hasPermissionTo('project.administrator'),
                function ($query) {
                    $centreIds = auth()->user()->project->centres->pluck('id')->toArray();
                    return $query->whereIn('centre_id', $centreIds);
                }
            )
            ->where('tenant_id', getTenant())
            ->whereIn('type', array(User::TYPE_STUDENT, User::TYPE_ALUMNI))

            ->latest()
            ->get();
    }

    public function map($student): array
    {

        $type1 = PlacementType::where('type', PlacementType::TYPE1)->first()->id;
        $type2 = PlacementType::where('type', PlacementType::TYPE2)->first()->id;
        $type3 = PlacementType::where('type', PlacementType::TYPE3)->first()->id;
        $placementInit = $student->placements->where('placement_type_id', $type1)->first();
        $placementAfter3 = $student->placements->where('placement_type_id', $type2)->first();
        $placementAfter6 = $student->placements->where('placement_type_id', $type3)->first();
        $this->batchStatus = "";
        if (isset($student->studentDetail->batch_id) && $student->studentDetail->batch->status == 0) {
            $this->batchStatus = "Inactive";
        } elseif (isset($student->studentDetail->batch_id) && $student->studentDetail->batch->status == 1) {
            $this->batchStatus = "Ongoing";
        } elseif (isset($student->studentDetail->batch_id) && $student->studentDetail->batch->status == 2) {
            $this->batchStatus = "Completed";
        }
        
        $values = [];

        $values = [
            $student->id,
            $student->name,
            $student->gender,
            isset($student->studentDetail->date_of_birth) ?
                ($student->studentDetail->date_of_birth->format('d-m-Y') ?: null) : null,
            isset($student->studentDetail->date_of_birth) ? $student->studentDetail->date_of_birth->age ?? null : null,
            $student->email ?? "",
            $student->mobile ?? "",
            isset($student->studentDetail->trade_id) ?
                ($student->studentDetail->trade->name ?: null) : null,
            $student->centre->name ?? "",
            $student->studentDetail->educationalQualification->name ?? "",
            config('staticcontent.maritalStatus.' . $student->studentDetail->marital_status) ?? "",
            $student->studentDetail->experience ?? "",
            $student->studentDetail->last_month_salary ?? "",
            $student->studentDetail->guardian_name ?? "",
            $student->studentDetail->guardian_mobile ?? "",
            $student->studentDetail->guardian_income ?? "",
            $student->studentDetail->guardian_occupation ?? "",
            isset($student->studentDetail->batch_id) ?
                (date('Y-m-d', strtotime($student->studentDetail->batch->start_date)) ?: null) : null,
            isset($student->studentDetail->batch_id) ?
                (date('Y-m-d', strtotime($student->studentDetail->batch->end_date)) ?: null) : null,
            isset($student->studentDetail->batch_id) ?
                ($student->studentDetail->batch->name ?: null) : null,
            $this->batchStatus,
            $this->projectName,
            $this->phaseName,
            date('Y-m-d', strtotime($this->startDate)),
            date('Y-m-d', strtotime($this->endDate)),
            $student->centre->organisation->name ?? "",
            $student->created_at->format('d-m-Y') ?? "",
            $student->studentDetail->contactability == 1 ? 'yes' : 'no',
            $student->studentDetail->not_contactable_reason ?? "",
            $student->studentDetail->interview1_company_name ?? "",
            isset($student->studentDetail->interview1_date) ?
                (date('d-m-Y', strtotime($student->studentDetail->interview1_date)) ?: null) : null,
            config('staticcontent.interview_result.' . $student->studentDetail->interview1_result) ?? "",
            $student->studentDetail->interview2_company_name ?? "",
            isset($student->studentDetail->interview2_date) ?
                (date('d-m-Y', strtotime($student->studentDetail->interview2_date)) ?: null) : null,
            config('staticcontent.interview_result.' . $student->studentDetail->interview2_result) ?? "",
            $student->studentDetail->interview3_company_name ?? "",
            isset($student->studentDetail->interview3_date) ?
                (date('d-m-Y', strtotime($student->studentDetail->interview3_date)) ?: null) : null,
            config('staticcontent.interview_result.' . $student->studentDetail->interview3_result) ?? "",
            $student->studentDetail->placed == 1 ? 'yes' : 'no',
            config('staticcontent.month_of_joining.' . $student->studentDetail->month_of_joining) ?? "",
            $student->studentDetail->date_of_updation ?? "",
            $student->studentDetail->remarks ?? "",
        ];
        if ($placementInit && isset($placementInit->placementStatus)) {
            $values[] = $placementInit->placementStatus->name;
            $placementInitStatus = $placementInit->placementStatus->type;
        } else {
            $values[] = "";
            $placementInitStatus = "";
        }
        $values = $this->getPlacementInit($placementInit, $placementInitStatus, $values);

        if ($placementAfter3 && isset($placementAfter3->placementStatus)) {
            $placementAfter3Status = $placementAfter3->placementStatus->type;
            $values[] = $placementAfter3->placementStatus->name;
        } else {
            $values[] = '';
            $placementAfter3Status = '';
        }
        $values = $this->getPlacementAfter3($placementAfter3, $placementAfter3Status, $values);

        $values[] = $student->studentDetail->updated_email ?? "";
        $values[] = $student->studentDetail->updated_mobile ?? "";

        if ($placementAfter6 && isset($placementAfter6->placementStatus)) {
            $placementAfter6Status = $placementAfter6->placementStatus->type;
            $values[] = $placementAfter6->placementStatus->name;
        } else {
            $values[] = '';
            $placementAfter6Status = '';
        }
        $values = $this->getPlacementAfter6($placementAfter6, $placementAfter6Status, $values);
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
            trans('admin.batch_completion_status'),
            trans('admin.project_name'),
            trans('admin.phase_name'),
            trans('admin.phase_start_date'),
            trans('admin.phase_end_date'),
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
            trans('admin.placement_status'),
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

    public function getPhaseDetails($phaseId) { 
        return Phase::find($phaseId);
    }
}
