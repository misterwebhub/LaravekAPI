<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\ProgramsToOrganisationRequest;
use App\Http\Resources\v1\ProgramsToOrganisationResource;
use App\Models\Organisation;
use App\Repositories\v1\ProgramsToOrganisationRepository;
use Illuminate\Http\Request;

class ProgramsToOrganisationController extends Controller
{
    private $programsToOrganisationRepository;

    /**
     * @param ProgramsToOrganisationRepository $programsToOrganisationRepository
     */
    public function __construct(ProgramsToOrganisationRepository $programsToOrganisationRepository)
    {
        $this->programsToOrganisationRepository = $programsToOrganisationRepository;
        $this->middleware('permission:organisation.view', ['only' => ['listPrograms']]);
        $this->middleware('permission:organisation.update', ['only' => ['assignPrograms']]);
    }

    /**
     * @param Request $request
     * @param  $organisation
     * @return [json]
     */
    public function listPrograms(Request $request, Organisation $organisation)
    {
        $this->authorize('view', $organisation);
        $programs = $this->programsToOrganisationRepository->listPrograms(
            $request->all(),
            $organisation,
            $request->user()
        );
        return ProgramsToOrganisationResource::collection($programs);
    }

    /**
     * @param ProgramsToOrganisationRequest $request
     * @param  $organisation
     * @return [json]
     */
    public function assignPrograms(ProgramsToOrganisationRequest $request, Organisation $organisation)
    {
        $this->authorize('update', $organisation);
        $organisation = $this->programsToOrganisationRepository->assignPrograms($request->all(), $organisation);
        return ProgramsToOrganisationResource::collection($organisation->program)
            ->additional(['message' => trans('admin.organisation_program_assigned')]);
    }
}
