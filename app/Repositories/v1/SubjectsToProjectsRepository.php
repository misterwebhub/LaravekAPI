<?php

namespace App\Repositories\v1;

/**
 * Class SubjectsToProjectsRepository
 * @package App\Repositories
 */
class SubjectsToProjectsRepository
{
    public function assignSubject($request, $project)
    {
        $subjects = array_filter($request['subject']);
        if (!empty($subjects)) {
            $currentSubjects = $project->subjects()->pluck('subject_id')->toArray();
            $deletedSubjects = array_diff($currentSubjects, $subjects);
            $project->subjects()->sync($subjects);
            $projectCentres = $project->centres;
            foreach ($projectCentres as $projectCentre) {
                $projectCentre->subjects()->syncWithoutDetaching($subjects);
                $projectCentre->subjects()->detach($deletedSubjects);
                $batches = $projectCentre->batches;
                foreach ($batches as $batch) {
                    $batch->subjects()->syncWithoutDetaching($subjects);
                    $batch->subjects()->detach($deletedSubjects);
                }
            }
        }
        return $project;
    }
}
