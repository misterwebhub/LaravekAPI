<?php

namespace App\Repositories\v1;

use App\Models\Centre;
use Spatie\QueryBuilder\QueryBuilder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CentreExport;
use App\Imports\CentreImport;
use App\Imports\CentreDistrictImport;
use App\Imports\CentreStateImport;
use Illuminate\Support\Facades\Storage;
use App\Models\Trade;
use App\Models\Approval;
use App\Models\User;
use App\Models\PhaseUser;
use App\Models\CentrePhase;
use App\Services\CentreOrganizationCustomSort;
use App\Services\Filter\CentreCustomFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * [Description CentreRepository]
 */
class CentreRepository
{
    /**
     * List all centres
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request, User $user)
    {
        if (auth()->user()->hasPermissionTo('program.administrator')) {
            $centres = QueryBuilder::for(Centre::class)

                ->join('organisations', 'centres.organisation_id', '=', 'organisations.id')

                ->join('organisation_program', 'organisations.id', '=', 'organisation_program.organisation_id')

                ->select('centres.*')

                ->where('organisation_program.program_id', auth()->user()->program_id);
        } elseif (auth()->user()->hasPermissionTo('project.administrator')) {
            $centres = QueryBuilder::for(Centre::class)

                ->join('centre_project', 'centres.id', '=', 'centre_project.centre_id')

                ->select('centres.*')

                ->where('centre_project.project_id', auth()->user()->project_id);
        } else {
            $centres = QueryBuilder::for(Centre::class)
                ->when($user->hasPermissionTo('organisation.administrator'), function ($query) use ($user) {
                    return $query->where('organisation_id', $user->organisation_id);
                })
                ->when($user->hasPermissionTo('centre.administrator'), function ($query) use ($user) {
                    return $query->where('id', $user->centre_id)
                        ->when($user->hasPermissionTo('activity.needs.approval'), function ($query) use ($user) {
                            return $query->where('created_by', $user->id);
                        })
                        ->when(
                            (!$user->hasPermissionTo('activity.needs.approval') && !$user->hasRole('super-admin')
                            ),
                            function ($query) {
                                return $query->where('is_approved', 1);
                            }
                        );
                });
        }
        $centres = $centres->where('centres.tenant_id', getTenant())
            ->with(['centreType', 'state', 'district', 'organisation', 'projects']);
        $totalCount = $centres->get()->count();
        $centres = $centres
            ->allowedFilters(
                [
                    'name', 'status', 'centre_type.id', 'working_mode', 'email', 'mobile',
                    'website', 'organisation.name', 'location', 'projects.name',
                    AllowedFilter::exact('organisation_id'),
                    AllowedFilter::custom('search_value', new CentreCustomFilter()),
                ]
            )
            ->allowedSorts(
                [
                    'name', 'email', 'mobile', 'website', 'location', 'status',
                    AllowedSort::custom('organisation.name', new CentreOrganizationCustomSort()),
                ]
            )
            ->latest()
            ->paginate($request['limit'] ?? null);
        return ['centres' => $centres, 'total_count' => $totalCount];
    }

    /**
     * Create a new Centre
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request, $user)
    {
        if ($user->hasPermissionTo('activity.needs.approval')) {
            $centre = new Centre();
            $centre->is_approved = Centre::TYPE_NOT_APPROVED;
            $centre->activation_code = Str::random(15);
            $centre = $this->setCentre($request, $centre);
            $centre->save();
            $approval = new Approval();
            $approval = $this->setApproval($approval, $centre);
            $approval->save();
        } else {
            $centre = new Centre();
            $centre->activation_code = Str::random(15);
            $centre = $this->setCentre($request, $centre);
            $centre->save();
        }
        return $centre;
    }

    /**
     * Delete a Centre
     * @param mixed $centre
     *
     * @return [type]
     */
    public function destroy($centre)
    {
        if ($centre->is_anywhere_learning == Centre::TYPE_IS_ANYWHERE_LEARNING) {
            throw ValidationException::withMessages([
                'error' => trans('admin.centre_is_anywhere_learning'),
            ]);
        }
        DB::beginTransaction();
        try {
            $centre->users()->delete();
            $centre->delete();
            PhaseUser::where('centre_id', $centre->id)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
            CentrePhase::where('centre_id', $centre->id)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
        }
    }

    /**
     * Update Centre
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [json]
     */
    public function update($request, $centre)
    {
        $centre = $this->setCentre($request, $centre);
        $centre->update();
        return $centre;
    }

    /**
     * Update status of Centre
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [type]
     */
    public function updateStatus($request, $centre)
    {
        $centre = $this->setStatus($centre, $request);
        $centre->update();
        return $centre;
    }

    /**
     * Set Centre Data
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [collection]
     */
    private function setCentre($request, $centre)
    {
        $centre->name = $request['name'];
        $centre->email = $request['email'];
        $centre->organisation_id = $request['organisation'];
        $centre->centre_type_id = $request['centre_type'];
        $centre->working_mode = $request['working_mode'];
        $centre->mobile = $request['mobile'] ?? null;
        $centre->state_id = $request['state'];
        $centre->district_id = $request['district'];
        $centre->city = $request['city'] ?? null;
        $centre->location = $request['location'] ?? null;
        $centre->website = $request['website'] ?? null;
        $centre->address = $request['address'] ?? null;
        $centre->pincode = isset($request['pincode']) ? ($request['pincode'] ?: null) : null;
        $centre->target_students = isset($request['target_students']) ? ($request['target_students'] ?: 0) : 0;
        $centre->target_trainers = isset($request['target_trainers']) ? ($request['target_trainers'] ?: 0) : 0;
        $centre->partnership_type_id = $request['partnership_type'] ?? null;
        $centre->center_id = $request['center_id'] ?? null;
        $centre->tenant_id = getTenant();
        return $centre;
    }

    /**
     * Set Approval Data
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [collection]
     */
    private function setApproval($approval, $centre)
    {
        $approval->type = Approval::TYPE_SINGLE;
        $approval->title = trans('admin.centre_approval_created');
        $approval->reference_id = $centre->id;
        $approval->model = Approval::TYPE_CENTRE_MODEL;
        $approval->status = Approval::TYPE_NEEDS_APPROVAL;
        return $approval;
    }

    /**
     * @param mixed $centre
     * @param mixed $request
     *
     * @return [type]
     */
    private function setStatus($centre, $request)
    {
        switch ($request['type']) {
            case 'job':
                $centre->allow_job = $request['status'];
                break;
            case 'resource':
                $centre->allow_resource = $request['status'];
                break;
            case 'active':
                $centre->active = $request['status'];
                break;
            case 'registration':
                $centre->allow_registration = $request['status'];
                break;
            case 'lesson':
                $centre->lesson_lock = $request['status'];
                break;
            case 'auto-update':
                $centre->auto_update_phase = $request['status'];
                break;
            default:
                $centre->status = $request['status'];
                break;
        }
        return $centre;
    }

    /**
     * Export centre
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function exportCentre($request)
    {
        $fileName = "centre_downlods/" . time() . "centres.csv";
        Excel::store(new CentreExport(), $fileName, 's3');
        return generateTempUrl($fileName);
    }

    /**
     * Import Centres
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function importCentre($request)
    {
        $approvalConst = $this->approvalConstCreate();
        $import = new CentreImport($approvalConst);
        Excel::import($import, $request['centre_upload_file']);
        return $import->data;
    }

    public function approvalConstCreate()
    {
        $approvalConst = Str::uuid();
        $approval = Approval::where('reference_id', $approvalConst)->first();
        if ($approval) {
            $approvalConst = $this->approvalConstCreate();
        }
        return $approvalConst;
    }

    /**
     * List trades corresponding to a centre
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function getTrades(Centre $centre)
    {
        $centerType = $centre->centreType->type;
        return Trade::where('tenant_id', getTenant())
            ->where('type', $centerType)
            ->get();
    }

    /**
     * Configure batch alumni button
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function configureBatchAlumni($request)
    {
        $centre = Centre::where('id', $request['centre_id'])->first();
        $centre->batch_alumni_configure = $request['configure_batch_alumni'];
        $centre->batch_end_interval = isset($request['batch_end_interval']) ?
            ($request['batch_end_interval'] ?: null) : null;
        $centre->update();
        return $centre;
    }

    /**
     * Configure allow batch
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function configureAllowBatch($request)
    {
        $centre = Centre::where('id', $request['centre_id'])->first();
        $centre->allow_batch = $request['configure_allow_batch'];
        $centre->update();
        return $centre;
    }
    /**
     * Import Centres
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function importCentreDistrictChange($request)
    {
        $approvalConst = $this->approvalConstCreate();
        $import = new CentreDistrictImport($approvalConst);
        Excel::import($import, $request['centre_upload_file']);
        return $import->data;
    }
     /**
     * Import Centres
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function getMismatchStatesOfCentre($request)
    {

        $approvalConst = $this->approvalConstCreate();
        $import = new CentreStateImport($approvalConst);
        Excel::import($import, $request['centre_upload_file']);
        return $import->data;
    }
}
