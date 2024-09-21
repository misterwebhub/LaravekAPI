<?php

namespace App\Repositories\v1;

use Spatie\QueryBuilder\QueryBuilder;

/**
 * Class ProgramsToOrganisationRepository
 * @package App\Repositories
 */
class ProgramsToOrganisationRepository
{
    /**
     * List all programs corresponding to a organisation
     *
     * @param mixed $request
     * @param mixed $organisation
     *
     * @return [type]
     */
    public function listPrograms($request, $organisation, $user)
    {
        $programQuery = $organisation->program();
        $programs =  QueryBuilder::for($programQuery)
            ->when($user->hasPermissionTo('organisation.administrator'), function ($query) use ($user) {
                return $query->where('organisation_id', $user->organisation_id);
            })
            ->when($user->hasPermissionTo('program.administrator'), function ($query) use ($user) {
                return $query->where('program_id', $user->program_id);
            })
            ->allowedSorts('name')
            ->allowedFilters(['name']);
        if (isset($request['limit'])) {
            $programs = $programs->paginate($request['limit'] ?? null);
        } else {
            $programs = $programs->get();
        }
        return $programs;
    }

    /**
     * Assign programs to an organisation
     *
     * @param mixed $request
     * @param mixed $organisation
     *
     * @return [type]
     */
    public function assignPrograms($request, $organisation)
    {
        $programs = array_filter($request['program']);
        if (!empty($programs)) {
            $organisation->program()->sync($programs);
        }
        return $organisation;
    }
}
