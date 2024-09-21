<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class AdminUserExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return QueryBuilder::for(User::class)
            ->allowedFilters(['name', AllowedFilter::scope('role')->ignore(null)])
            ->where('tenant_id', getTenant())->where('type', User::TYPE_ADMIN)
            ->latest()
            ->get();
    }

    public function map($adminUser): array
    {
        return [

            $adminUser->id,
            $adminUser->name,
            $adminUser->email,
            $adminUser->mobile,
            ucwords(str_replace('-', ' ', ($adminUser->roles()->first()->name  ?? null))),
            $this->getRole($adminUser),



        ];
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
    private function getRole($adminUser)
    {


        if ($adminUser->centre_id) {
            $role = $adminUser->centre->name ?? null;
        } elseif ($adminUser->program_id) {
            $role = $adminUser->program->name ?? null;
        } elseif ($adminUser->project_id) {
            $role = $adminUser->project->name ?? null;
        } elseif ($adminUser->organisation_id) {
            $role = $adminUser->organisation->name ?? null;
        } else {
            $role = null;
        }
        return $role;
    }
}
