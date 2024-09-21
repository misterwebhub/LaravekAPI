<?php

namespace App\Exports;

use App\Models\Centre;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CentreExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        if (auth()->user()->hasPermissionTo('program.administrator')) {
            $centres = QueryBuilder::for(Centre::class)

                ->join('organisations', 'centres.organisation_id', '=', 'organisations.id')

                ->join('organisation_program', 'organisations.id', '=', 'organisation_program.organisation_id')

                ->select('centres.*')

                ->where('organisation_program.program_id', auth()->user()->program_id);
        } elseif (auth()->user()->hasPermissionTo('project.administrator')) {
            $centres = QueryBuilder::for(Centre::class)

                ->join('centre_project', 'centres.id', '=', 'centre_project.centre_id')

                ->select('centres.*')

                ->where('centre_project.project_id', auth()->user()->project_id);
        } else {
            $centres = QueryBuilder::for(Centre::class)
                ->when(auth()->user()->hasPermissionTo('organisation.administrator'), function ($query) {
                    return $query->where('organisation_id', auth()->user()->organisation_id);
                })
                ->when(auth()->user()->hasPermissionTo('centre.administrator'), function ($query) {
                    return $query->where('id', auth()->user()->centre_id);
                })
                ->when(auth()->user()->hasPermissionTo('activity.needs.approval'), function ($query) {
                    return $query->where('created_by', auth()->user()->id);
                })
                ->when(
                    !auth()->user()->hasPermissionTo('activity.needs.approval')
                        && !auth()->user()->hasRole('super-admin'),
                    function ($query) {
                        return $query->where('is_approved', 1);
                    }
                );
        }
        $centres = $centres->allowedFilters([
            'name', 'status', 'centre_type.id', 'working_mode',
            AllowedFilter::exact('organisation_id')
        ])
            ->where('tenant_id', getTenant())
            ->with(['centreType', 'state', 'district', 'organisation', 'projects'])
            ->latest()
            ->get();
        return $centres;
    }

    public function map($centre): array
    {
        return [
            $centre->id,
            $centre->name,
            $centre->organisation->name ?? "",
            implode(',', $centre->projects->pluck('name')->toArray()) ?? "",
            $centre->centreType->name ?? "",
            $centre->partnershipType->name ?? "",
            $centre->target_students ?? "",
            $centre->target_trainers ?? "",
            config('staticcontent.workingMode.' . $centre->working_mode) ?? "",
            $centre->location ?? "",
            $centre->email ?? "",
            $centre->mobile ?? "",
            $centre->website ?? "",
            $centre->address ?? "",
            $centre->state->name ?? "",
            $centre->district->name ?? "",
            $centre->city ?? "",
            config('staticcontent.status.' . $centre->status) ?? "",
        ];
    }

    public function headings(): array
    {
        return [
            trans('admin.id'),
            trans('admin.centre_name'),
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
