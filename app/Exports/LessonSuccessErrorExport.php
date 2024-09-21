<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Models\Language;

class LessonSuccessErrorExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public $lesson;

    public function __construct($lesson)
    {
        $this->lesson = $lesson;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->lesson;
    }

    public function headings(): array
    {
        $languages = Language::orderBy('order')->get();
        $columnArray = ['Lesson Url', 'Lesson Download Path', 'Lesson Index Path'];
        $fields = [
            trans('admin.id'),
            trans('admin.subject_name'),
            trans('admin.course_name'),
            trans('admin.lesson_order'),
            trans('admin.lesson_name'),
            trans('admin.lesson_type'),
            trans('admin.facilitator_access'),
            trans('admin.student_access'),
            trans('admin.web_access'),
            trans('admin.mobile_access'),
            trans('admin.lesson_category'),
            trans('admin.description'),
            trans('admin.duration'),
        ];
        foreach ($languages as $language) {
            foreach ($columnArray as $column) {
                $fields[] = $language->name . ' ' . $column;
            }
        }
        $fields[] = trans('admin.is_portrate');
        $fields[] = trans('admin.is_assessment');
        return $fields;
    }
}
