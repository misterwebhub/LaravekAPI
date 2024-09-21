<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\QueryBuilder\QueryBuilder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Carbon\Carbon;

class FacilitatorsExport implements FromQuery, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function query()
    {
        if (auth()->user()->hasPermissionTo('program.administrator')) {
            $facilitators = QueryBuilder::for(User::class)

                ->join('organisations', 'users.organisation_id', '=', 'organisations.id')

                ->join('organisation_program', 'organisations.id', '=', 'organisation_program.organisation_id')

                ->select('users.*')

                ->where('organisation_program.program_id', auth()->user()->program_id);
        } elseif (auth()->user()->hasPermissionTo('project.administrator')) {
            $facilitators = QueryBuilder::for(User::class)

                ->join('centres', 'users.centre_id', '=', 'centres.id')

                ->join('centre_project', 'centres.id', '=', 'centre_project.centre_id')

                ->select('users.*')

                ->where('centre_project.project_id', auth()->user()->project_id);
        } else {
            $facilitators =  QueryBuilder::for(User::class)
                ->when(auth()->user()->hasPermissionTo('centre.administrator'), function ($query) {
                    return $query->where('centre_id', auth()->user()->centre_id);
                })
                ->when(auth()->user()->hasPermissionTo('organisation.administrator'), function ($query) {
                    return $query->where('organisation_id', auth()->user()->organisation_id);
                })
                ->when(auth()->user()->hasPermissionTo('activity.needs.approval'), function ($query) {
                    return $query->where('created_by', auth()->user()->id);
                })
                ->when(!auth()->user()->hasPermissionTo('activity.needs.approval'), function ($query) {
                    return $query->where('is_approved', 1);
                });
        }
        $facilitators = $facilitators->with('organisation', 'facilitatorDetail', 'centre')
            ->allowedFilters(['name'])
            ->where('users.tenant_id', getTenant())->where('type', User::TYPE_FACILITATOR)
            ->latest();
        return $facilitators;
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->organisation->name ?? "",
            $user->centre->name ?? "",
            $user->centre->state->name ?? "",
            ucfirst($user->gender) ?? "",
            $user->facilitatorDetail->designation ?? "",
            $user->email ?? "",
            isset($user->facilitatorDetail->date_of_birth) ? Carbon::parse($user->facilitatorDetail->date_of_birth)->format('Y-m-d') : '',
            $user->mobile ?? "",
            $user->facilitatorDetail->qualification ?? "",
            $user->facilitatorDetail->experience ?? "",
            $user->is_super_facilitator == User::SUPER_FACILITATOR ? 'yes' : 'no',
            $user->is_master_trainer  == User::MASTER_TRAINER ? 'yes' : 'no',
            (isset($user->facilitatorDetail->user_approved) && $user->facilitatorDetail->user_approved) == User::TYPE_IS_APPROVED ? 'yes' : 'no',
        ];
    }

    public function headings(): array
    {
        return [
            trans('admin.id'),
            trans('admin.facilitator_name'),
            trans('admin.organisation_name'),
            trans('admin.centre_name'),
            trans('admin.state'),
            trans('admin.gender'),
            trans('admin.designation'),
            trans('admin.email'),
            trans('admin.dob'),
            trans('admin.phone'),
            trans('admin.highest_qualification'),
            trans('admin.Work_experience'),
            trans('admin.is_super_facilitator'),
            trans('admin.is_master_trainer'),
            trans('admin.is_approved'),
        ];
    }
}
