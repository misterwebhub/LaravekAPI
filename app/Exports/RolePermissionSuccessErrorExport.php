<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Models\Role;
use Spatie\QueryBuilder\QueryBuilder;

class RolePermissionSuccessErrorExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public $rolePermisssion;

    public function __construct($rolePermisssion)
    {
        $this->rolePermisssion = $rolePermisssion;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->rolePermisssion;
    }

    public function headings(): array
    {
        $roles =  QueryBuilder::for(Role::class)
            ->where('type', 1)
            ->with('permissions')
            ->latest()
            ->get();
        $data[] = '#';
        foreach ($roles as $role) {
            $data[] =  $role->name;
        }
        return $data;
    }
}
