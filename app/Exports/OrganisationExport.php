<?php

namespace App\Exports;

use App\Models\Organisation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\QueryBuilder\QueryBuilder;

class OrganisationExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        if (auth()->user()->hasPermissionTo('program.administrator')) {
            $organisations = QueryBuilder::for(Organisation::class)

                ->join('organisation_program', 'organisations.id', '=', 'organisation_program.organisation_id')

                ->select('organisations.*')

                ->where('organisation_program.program_id', auth()->user()->program_id);
        } elseif (auth()->user()->hasPermissionTo('project.administrator')) {
            $organisations = QueryBuilder::for(Organisation::class)
                ->join('centres', 'centres.organisation_id', '=', 'organisations.id')
                ->join('centre_project', 'centres.id', '=', 'centre_project.centre_id')
                ->select('organisations.*')
                ->where('centre_project.project_id', auth()->user()->project_id);
        } else {
            $organisations = QueryBuilder::for(Organisation::class)

                ->when(auth()->user()->hasPermissionTo('organisation.administrator'), function ($query) {
                    return $query->where('id', auth()->user()->organisation_id);
                })
                ->when(auth()->user()->hasPermissionTo('activity.needs.approval'), function ($query) {
                    return $query->where('created_by', auth()->user()->id);
                })
                ->when(!auth()->user()->hasPermissionTo('activity.needs.approval'), function ($query) {
                    return $query->where('is_approved', 1);
                });
        }
        $organisations = $organisations->allowedFilters(['name', 'status'])
            ->where('organisations.tenant_id', getTenant())
            ->with(['state', 'district', 'program'])
            ->latest()
            ->get();
        return $organisations;
    }

    public function map($organisation): array
    {
        return [
            $organisation->id,
            $organisation->name,
            $organisation->email ?? "",
            $organisation->mobile ?? "",
            $organisation->website ?? "",
            $organisation->address ?? "",
            $organisation->pincode ?? "",
            $organisation->state->name ?? "",
            $organisation->district->name ?? "",
            $organisation->city ?? "",
            implode(',', $organisation->program->pluck('name')->toArray()) ?? "",

        ];
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
