<?php

namespace App\Exports;

use App\Models\Language;
use App\Models\Lesson;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LessonExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    public function __construct()
    {
        $this->languages = Language::orderBy("order", 'ASC')->get();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return QueryBuilder::for(Lesson::class)
            ->allowedFilters(['name', AllowedFilter::exact('subject_id'), AllowedFilter::exact('status')])
            ->with('lessonType', 'lessonCategory', 'subject', 'course')
            ->where('lessons.tenant_id', getTenant())
            ->latest('lessons.created_at')
            ->get();
    }

    public function map($lesson): array
    {
        $values = [
            $lesson->id,
            $lesson->subject->name ?? "",
            $lesson->course->name ?? "",
            $lesson->lesson_order ?? "",
            $lesson->name,
            $lesson->lessonType->name ?? "",
            $lesson->lessonCategory->name ?? "",
            $lesson->web_access == 1 ? 'yes' : 'no',
            $lesson->mobile_access == 1 ? 'yes' : 'no',
            $lesson->student_access == 1 ? 'yes' : 'no',
            $lesson->facilitator_access == 1 ? 'yes' : 'no',
            $lesson->description ?? "",
            $lesson->duration ?? ""
        ];
        foreach ($this->languages as $language) {
            $values[] = $lesson->lessonLinks->where('id', $language->id)->first()->pivot->folder_path ?? "";
            $values[] = $lesson->lessonLinks->where('id', $language->id)->first()->pivot->download_path ?? "";
            $values[] = $lesson->lessonLinks->where('id', $language->id)->first()->pivot->index_path ?? "";
        }
        $values[] = $lesson->is_portrait_view == 1 ? 'yes' : 'no';
        $values[] = $lesson->is_assessment == 1 ? 'yes' : 'no';
        return $values;
    }

    public function headings(): array
    {
        $languages = Language::orderBy("order", 'ASC')->get();
        $columnArray = ['Lesson Url', 'Lesson Download Path', 'Lesson Index Path'];
        $fields = [
            trans('admin.id'),
            trans('admin.subject_name'),
            trans('admin.course_name'),
            trans('admin.lesson_order'),
            trans('admin.lesson_name'),
            trans('admin.lesson_type'),
            trans('admin.lesson_category'),
            trans('admin.web_access'),
            trans('admin.mobile_access'),
            trans('admin.student_access'),
            trans('admin.facilitator_access'),
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
