<?php

namespace App\Exports;

use App\Models\Permission;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RolePermissionExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $items;
    public function __construct($items)
    {
        $this->items = $items;
    }

    public function headings(): array
    {
        $data[] = '#';
        foreach ($this->items as $item) {
            $data[] =  $item->name;
        }
        return $data;
    }

    public function collection()
    {
        $permissions = Permission::distinct()->whereNotNull('sub_group')->get(['sub_group']);
        $permissions->push([
            'sub_group' => 'Miscellaneous',
        ]);
        $miscellaneousGroup = Permission::distinct()
            ->where('group', Permission::PERMISSION_MISCELLANEOUS)->get(['title']);
        foreach ($miscellaneousGroup as $group) {
            $permissions->push([
                'sub_group' => $group['title'],
            ]);
        }
        return collect($permissions);
    }

    public function map($permissions): array
    {
        $values = [];
        $values[] =  $permissions['sub_group'];
        foreach ($this->items as $item) {
            $itemPermissions =  $item->getAllPermissions()
                ->where('sub_group', $permissions['sub_group'])
                ->pluck('group')->toArray();

            $data = [];
            if ($permissions['sub_group'] != 'Miscellaneous') {
                if (in_array(Permission::PERMISSION_CREATE, $itemPermissions)) {
                    $data = 'Write';
                } elseif (in_array(Permission::PERMISSION_UPDATE, $itemPermissions)) {
                    $data = 'Write';
                } elseif (in_array(Permission::PERMISSION_READ, $itemPermissions)) {
                    $data = 'Read';
                } elseif (in_array(Permission::PERMISSION_DELETE, $itemPermissions)) {
                    $data = 'Write';
                } else {
                    $miscellaneousPermissions =  $item->getAllPermissions()
                        ->where('group', Permission::PERMISSION_MISCELLANEOUS)
                        ->pluck('title')
                        ->toArray();
                    if (in_array($permissions['sub_group'], $miscellaneousPermissions)) {
                        $data = 'yes';
                    }
                }
            } else {
                $otherPermissions =  $item->getAllPermissions()
                    ->pluck('group')->toArray();
                if (in_array(Permission::PERMISSION_MISCELLANEOUS, $otherPermissions)) {
                    $data = '';
                }
            }
            $values[] = $data;
        }
        return $values;
    }
}
