<?php

namespace App\Imports;

use App\Exports\LessonSuccessErrorExport;
use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\LessonCategory;
use App\Models\LessonType;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class LessonDataImport
 * @package App\Imports
 */
class LessonImport implements ToCollection, WithHeadingRow
{
    public $data;
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $randomSleep = rand(1, 10);
        sleep($randomSleep);
        $subjectNameArray = $collection->where('subject_name', '!=', null)
            ->unique('subject_name')->pluck('subject_name')->toArray();
        $subjectNameArray = array_map(function ($ee) {
            return trim($ee);
        }, $subjectNameArray);
        $subjectNameArray = collect($subjectNameArray);
        $subjects = getSubjectFromName($subjectNameArray); //Collection with ID and Name
        $finalResultAll = [];
        $finalResultErrors = [];
        $keyArray = array(
            0 => 'id',
            1 => 'subject_name',
            2 => 'course_name',
            3 => 'lesson_order',
            4 => 'lesson_name',
            5 => 'lesson_type',
            6 => 'facilitator_access',
            7 => 'student_access',
            8 => 'web_access',
            9 => 'mobile_access',
            10 => 'lesson_category',
            11 => 'description',
            12 => 'duration',
        );
        $subject_ids = $subjects->pluck('id')->toArray();
        $languages = Language::orderBy('order')->get();
        $courses = Course::whereIn('subject_id', $subject_ids)->get();
        $columnArray = ['lesson_url', 'lesson_download_path', 'lesson_index_path'];

        $x = 13;
        $langKeyArr = [];
        foreach ($languages as $language) {
            foreach ($columnArray as $column) {
                $keyArray[$x] = strtolower($language->name) . '_' . $column;
                $x++;
                $langKeyArr[] = strtolower($language->name) . '_' . $column;
            }
        }
        $keyArray[] = 'is_portrate';
        $keyArray[] = 'is_assessment';
        $errorCount = 0;
    try{
        foreach ($collection->chunk(10) as $chunk) {
            foreach ($chunk as $row) {
                $finalRes = [];
                $errors = [];

                $this->checkExcelFormat($row, $keyArray);
                $lessonDetails = $this->fetchdata($row, $subjects, $courses);

                foreach ($row as $key => $value) {
                    if (
                        $key == 'lesson_order' || $key == 'facilitator_access' || $key == 'student_access'
                        || $key == 'web_access' || $key == 'mobile_access' || $key == 'is_portrate'
                        || $key == 'is_assessment'
                    ) {
                        $finalRes[] = (string) $value;
                    } else {
                        $finalRes[] = (string) $value;
                    }
                }
                DB::beginTransaction();
                if ($row['id']) {
                    $lesson = Lesson::where('id', $row['id'])->first();
                    $errors = $this->validateFields($row['id'], $lessonDetails, $row);
                } else {
                    $lesson = new Lesson();
                    $errors = $this->validateFields("", $lessonDetails, $row);
                }
                if (count($errors) == 0) {
                    $lesson = $this->setLesson($lesson, $lessonDetails);
                        if ($row['id']) {
                            $countCheck = Lesson::where('name', $lessonDetails['lesson_name'])
                                ->where('subject_id', $lessonDetails['subject_id'])
                                ->where('course_id', $lessonDetails['course_id'])->where('id', '!=', $row['id'])->count();
                            if ($countCheck == 0) {
                                $lesson->update();
                            } else {
                                $errorCount++;
                                $finalRes[] = "Failed " . trans('admin.lesson_exist');;
                                $finalResultAll[] = $finalRes;
                                $finalResultErrors[] = $finalRes;
                            }
                        } else {
                            $countCheck = Lesson::where('name', $lessonDetails['lesson_name'])
                                ->where('subject_id', $lessonDetails['subject_id'])
                                ->where('course_id', $lessonDetails['course_id'])->count();
                            if ($countCheck == 0) {
                                $lesson->save();
                            } else {
                                $errorCount++;
                                $finalRes[] = "Failed " . trans('admin.lesson_exist');;
                                $finalResultAll[] = $finalRes;
                                $finalResultErrors[] = $finalRes;
                            }
                        }
                    $langKeyArrChunk = array_chunk($langKeyArr, 3);
                    $link = [];
                    foreach ($langKeyArrChunk as $eachChunk) {
                        $lessonUrl = "";
                        $downloadPath = "";
                        $indexPath = "";
                        foreach ($eachChunk as $lkey) {
                            $explodeArr = explode('_', $lkey);
                            $langName = ucfirst($explodeArr[0]);
                            $langId = $languages->where('name', $langName)->first()->id;
                            unset($explodeArr[0]);
                            $keyName = implode("_", $explodeArr);
                            if ($keyName == 'lesson_url') {
                                $lessonUrl = $row[$lkey];
                            }
                            if ($keyName == 'lesson_download_path') {
                                $downloadPath = $row[$lkey];
                            }
                            if ($keyName == 'lesson_index_path') {
                                $indexPath = $row[$lkey];
                            }
                        }
                        if (!empty($lessonUrl) || !empty($downloadPath) || !empty($indexPath)) {
                            $link[$langId]['folder_path'] = $lessonUrl;
                            $link[$langId]['download_path'] = $downloadPath;
                            $link[$langId]['index_path'] = $indexPath;
                            $link[$langId]['tenant_id'] = getTenant();
                        }
                    }
                    $lesson->lessonLinks()->sync($link);
                    DB::commit();
                    $finalRes[] = "Success";
                    $finalResultAll[] = $finalRes;
                } else {
                    DB::rollback();
                    $errorCount++;
                    $finalRes[] = "Failed " . implode(',', $errors);
                    $finalResultAll[] = $finalRes;
                    $finalResultErrors[] = $finalRes;
                }
            }
        }
    }catch(Exception $e){
            DB::rollback();
    }
        $export = collect($finalResultAll);
        $errorExport = collect($finalResultErrors);
        $uniqid = Str::random();
        $errorFileName = 'lesson_downlods/error/' . 'error_' . $uniqid . '.csv';
        $fileName = 'lesson_downlods/' . $uniqid . '.csv';
        Excel::store(new LessonSuccessErrorExport($export), $fileName, 's3');
        Excel::store(new LessonSuccessErrorExport($errorExport), $errorFileName, 's3');
        $data['status'] = 1;
        $data['message'] = trans('admin.file_imported');
        if ($errorCount == 0) {
            $data['error_status'] = 0;
        } else {
            $data['error_status'] = 1;
            $data['error_file_name'] = generateTempUrl($errorFileName);
        }
        $data['uploaded_file_name'] = generateTempUrl($fileName);
        $this->data = $data;
    }

    private function checkExcelFormat($row, $keyArray)
    {
        $i = 0;
        foreach ($row as $key => $value) {
            if ($key != $keyArray[$i]) {
                throw ValidationException::withMessages(
                    array("file" =>
                    "Invalid Excel Format," . ucfirst(str_replace('_', ' ', $keyArray[$i])) . " Missing")
                );
            }
            $i++;
        }
    }

    private function fetchdata($row, $subjects, $courses)
    {
        $subjectDetails = $this->getCollectionCaseInsensitiveString(
            $subjects,
            'name',
            trim($row['subject_name'])
        )->first();
        $lessonDetails['subject_id'] = $subjectDetails->id ?? null;
        $courseDetails = $this->getCollectionCaseInsensitiveString(
            $courses,
            'name',
            trim($row['course_name'])
        )
            ->where('subject_id', $lessonDetails['subject_id'])->first();
        $lessonDetails['course_name'] = trim($row['course_name']) ?? null;
        $lessonDetails['course_id'] = $courseDetails->id ?? null;
        $lessonDetails['lesson_order'] = $row['lesson_order'] ?? 0;
        $lessonDetails['lesson_name'] = $row['lesson_name'] ?? null;
        if (in_array($row['facilitator_access'], ['no', 'n', '0', ""])) {
            $lessonDetails['facilitator_access'] = 0;
        } else {
            $lessonDetails['facilitator_access'] = 1;
        }
        if (in_array($row['student_access'], ['no', 'n', '0', ""])) {
            $lessonDetails['student_access'] = 0;
        } else {
            $lessonDetails['student_access'] = 1;
        }
        if (in_array($row['mobile_access'], ['no', 'n', '0', ""])) {
            $lessonDetails['mobile_access'] = 0;
        } else {
            $lessonDetails['mobile_access'] = 1;
        }
        if (in_array($row['web_access'], ['no', 'n', '0', ""])) {
            $lessonDetails['web_access'] = 0;
        } else {
            $lessonDetails['web_access'] = 1;
        }
        if (in_array(strtolower($row['is_portrate']), ['no', 'n', '0', ""])) {
            $lessonDetails['is_portrate'] = 0;
        } else if (in_array(strtolower($row['is_portrate']), ['yes', 'y', '1'])) {
            $lessonDetails['is_portrate'] = 1;
        }else{
            $lessonDetails['is_portrate'] = null;
        }
        if (in_array(strtolower($row['is_assessment']), ['no', 'n', '0', ""])) {
            $lessonDetails['is_assessment'] = 0;
        } else if (in_array(strtolower($row['is_assessment']), ['yes', 'y', '1'])) {
            $lessonDetails['is_assessment'] = 1;
        }else{
            $lessonDetails['is_assessment'] = null;
        }
        $LessonTypeDetails = LessonType::where('name', trim($row['lesson_type']))->first();
        $lessonDetails['lesson_type'] = $LessonTypeDetails->id ?? null;
        $LessonCategoryDetails = LessonCategory::where('name', trim($row['lesson_category']))->first();
        $lessonDetails['lesson_category'] = $LessonCategoryDetails->id ?? null;
        $lessonDetails['description'] = $row['description'] ?? null;
        $lessonDetails['duration'] = $row['duration'] ?? null;
        return $lessonDetails;
    }

    private function getCollectionCaseInsensitiveString($collection, $attribute, $value)
    {
        $collection = $collection->filter(function ($item) use ($attribute, $value) {
            return strtolower($item[$attribute]) == strtolower($value);
        });
        return $collection;
    }

    private function validateFields($id, $lessonDetails, $row)
    {
        $errors = [];
        if ($id) {
            $lesson = Lesson::where('id', $id)->first();
            if (!$lesson) {
                $errors[] = trans('admin.invalid_id');
            }
            $nameCount = Lesson::where('name', $lessonDetails['lesson_name'])
                ->where('subject_id', $lessonDetails['subject_id'])
                ->where('course_id', $lessonDetails['course_id'])->where('id', '!=', $id)->get()->count();
            if (!auth()->user()->can('lesson.update')) {
                $errors[] = trans('admin.lesson_no_update_permission');
            }
        } else {
            $nameCount = Lesson::where('name', $lessonDetails['lesson_name'])
                ->where('subject_id', $lessonDetails['subject_id'])
                ->where('course_id', $lessonDetails['course_id'])->count();
            if (!auth()->user()->can('lesson.create')) {
                $errors[] = trans('admin.lesson_no_create_permission');
            }
        }
        if (($lessonDetails['course_id'] == null || trim($lessonDetails['course_id']) == '') && trim($row['course_name']) != '') {
            $errors[] = trans('admin.invalid_course');
        }
        if ($nameCount > 0) {
            $errors[] = trans('admin.lesson_exist');
        }
        if (empty($lessonDetails['subject_id'])) {
            $errors[] = trans('admin.invalid_subject');
        }
        if (empty($lessonDetails['lesson_name'])) {
            $errors[] = trans('admin.lesson_name_missing');
        }
        if (empty($lessonDetails['lesson_type'])) {
            $errors[] = trans('admin.lesson_type_missing');
        }
        if (empty($lessonDetails['lesson_category'])) {
            $errors[] = trans('admin.lesson_category_missing');
        }
        return $errors;
    }

    private function setLesson($lesson, $lessonDetails)
    {
        $lesson->name = $lessonDetails['lesson_name'];
        $lesson->subject_id = $lessonDetails['subject_id'];
        $lesson->lesson_type_id = $lessonDetails['lesson_type'];
        $lesson->course_id = $lessonDetails['course_id'];
        $lesson->lesson_order = $lessonDetails['lesson_order'];
        $lesson->facilitator_access = $lessonDetails['facilitator_access'];
        $lesson->student_access = $lessonDetails['student_access'];
        $lesson->web_access = $lessonDetails['web_access'];
        $lesson->mobile_access = $lessonDetails['mobile_access'];
        $lesson->is_portrait_view = $lessonDetails['is_portrate'];
        $lesson->is_assessment = $lessonDetails['is_assessment'];
        $lesson->lesson_category_id = $lessonDetails['lesson_category'] ?? "";
        $lesson->status = Lesson::ACTIVE_STATUS;
        $lesson->tenant_id = getTenant();
        $lesson->description = $lessonDetails['description'];
        $lesson->duration = $lessonDetails['duration'];
        return $lesson;
    }
}
