<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\ProgramRequest;
use App\Http\Requests\v1\UpdateStatusRequest;
use App\Http\Resources\v1\ProgramResource;
use App\Models\Program;
use App\Repositories\v1\ProgramRepository;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    private $programRepository;

    /**
     * @param ProgramRepository $programRepository
     */
    public function __construct(ProgramRepository $programRepository)
    {
        $this->programRepository = $programRepository;
        $this->middleware('permission:program.view', ['only' => ['index']]);
        $this->middleware('permission:program.create', ['only' => ['store']]);
        $this->middleware('permission:program.update', ['only' => ['show', 'update', 'updateStatus']]);
        $this->middleware('permission:program.destroy', ['only' => ['destroy']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $programs = $this->programRepository->index($request->all(), $request->user());
        return ProgramResource::collection($programs['programs'])
            ->additional(['total_without_filter' => $programs['total_count']]);
    }

    /**
     * @param ProgramRequest $request
     *
     * @return [json]
     */
    public function store(ProgramRequest $request)
    {
        $this->authorize('create', Program::class);
        $program = $this->programRepository->store($request->all());
        return (new ProgramResource($program))
            ->additional(['message' => trans('admin.program_added')]);
    }


    /**
     * @param Program $program
     *
     * @return [json]
     */
    public function show(Program $program)
    {
        $this->authorize('view', $program);
        return new ProgramResource($program);
    }


    /**
     * @param ProgramRequest $request
     * @param Program $program
     *
     * @return [json]
     */
    public function update(ProgramRequest $request, Program $program)
    {
        $this->authorize('update', $program);
        $programs = $this->programRepository->update($request->all(), $program);
        return (new ProgramResource($programs))
            ->additional(['message' => trans('admin.program_updated')]);
    }


    /**
     * @param Program $program
     *
     * @return [json]
     */
    public function destroy(Program $program)
    {
        $this->authorize('delete', $program);
        $this->programRepository->destroy($program);
        return response(['message' => trans('admin.program_deleted')]);
    }


    /**
     * @param UpdateStatusRequest $request
     * @param Program $program
     *
     * @return [json]
     */
    public function updateStatus(UpdateStatusRequest $request, Program $program)
    {
        $this->authorize('update', $program);
        $programs = $this->programRepository->updateStatus($request->all(), $program);
        return (new ProgramResource($programs))
            ->additional(['message' => trans('admin.program_status_change')]);
    }
}
