<?php

namespace App\Repositories\v1;

use App\Exports\OrganisationExport;
use App\Imports\OrganisationImport;
use App\Models\Organisation;
use App\Models\Centre;
use App\Models\User;
use App\Models\CentrePhase;
use App\Models\PhaseUser;
use App\Models\Approval;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Services\Filter\OrganisationCustomFilter;
use Spatie\QueryBuilder\AllowedFilter;
use Carbon\Carbon;

/**
 * Class OrganisationRepository
 * @package App\Repositories
 */
class OrganisationRepository
{
    /**
     * List all organisations
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request, User $user)
    {
        if (auth()->user()->hasPermissionTo('program.administrator')) {
            $organisations = QueryBuilder::for(Organisation::class)->distinct()

                ->join('organisation_program', 'organisations.id', '=', 'organisation_program.organisation_id')

                ->select('organisations.*')

                ->where('organisation_program.program_id', auth()->user()->program_id);
        } elseif (auth()->user()->hasPermissionTo('project.administrator')) {
            $organisations = QueryBuilder::for(Organisation::class)->distinct()
                ->join('centres', 'centres.organisation_id', '=', 'organisations.id')
                ->join('centre_project', 'centres.id', '=', 'centre_project.centre_id')
                ->select('organisations.*')
                ->where('centre_project.project_id', auth()->user()->project_id);
        } else {
            $organisations = QueryBuilder::for(Organisation::class)->distinct()
                ->when($user->hasPermissionTo('organisation.administrator'), function ($query) use ($user) {
                    return $query->where('id', $user->centre->organisation_id);
                })
                ->when($user->hasPermissionTo('centre.administrator'), function ($query) use ($user) {
                    return $query->where('id', $user->centre->organisation_id);
                })
                ->when($user->hasPermissionTo('activity.needs.approval'), function ($query) use ($user) {
                    return $query->where('created_by', $user->id);
                })
                ->when(!$user->hasPermissionTo('activity.needs.approval'), function ($query) {
                    return $query->where('is_approved', 1);
                });
        }
        $organisations = $organisations->where('organisations.tenant_id', getTenant())
            ->with(['state', 'district', 'program']);
        $totalCount = $organisations->get()->count();
        $organisations = $organisations
            ->allowedFilters(
                [
                    'name', 'status', 'email', 'mobile', 'website', 'program.name',
                    AllowedFilter::custom('search_value', new OrganisationCustomFilter()),
                ]
            )
            ->allowedSorts(
                ['name', 'status', 'email', 'mobile', 'website']
            )
            ->latest()->paginate($request['limit'] ?? null);
        return ['organisations' => $organisations, 'total_count' => $totalCount];
    }

    /**
     * Create a new Organisation
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request, $user)
    {
        if ($user->hasPermissionTo('activity.needs.approval')) {
            $organisation = new Organisation();
            $organisation = $this->setOrganisation($request, $organisation);
            $organisation->is_approved = Organisation::TYPE_NOT_APPROVED;
            $organisation->save();
            $programs = array_filter($request['program']);
            if (!empty($programs)) {
                $organisation->program()->attach($programs);
            }
            $approval = new Approval();
            $approval = $this->setApproval($approval, $organisation);
            $approval->save();
        } else {
            $organisation = new Organisation();
            $organisation = $this->setOrganisation($request, $organisation);
            $organisation->save();
            $programs = array_filter($request['program']);
            if (!empty($programs)) {
                $organisation->program()->attach($programs);
            }
        }
        return $organisation;
    }

    /**
     * @param mixed $organisation
     *
     * @return [type]
     */
    public function destroy($organisation)
    {
        $centre = $organisation->centres()->distinct()->pluck('is_anywhere_learning')->toArray();
        if (in_array(Centre::TYPE_IS_ANYWHERE_LEARNING, $centre)) {
            throw ValidationException::withMessages([
                'error' => trans('admin.organisation_is_anywhere_learning'),
            ]);
        }
        DB::beginTransaction();
        try {
            $centres = $organisation->centres()->pluck('id');
            PhaseUser::whereIn('centre_id', $centres)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
            CentrePhase::whereIn('centre_id', $centres)->whereNull('deleted_at')->update(['deleted_at' => Carbon::now()->format('Y-m-d H:i:s')]);
            $organisation->centres()->delete();
            $organisation->users()->delete();
            $organisation->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
        }
    }

    /**
     * @param mixed $request
     * @param mixed $organisation
     *
     * @return [type]
     */
    public function update($request, $organisation)
    {
        $organisation = $this->setOrganisation($request, $organisation);
        $organisation->update();
        $programs = array_filter($request['program']);
        if (!empty($programs)) {
            $organisation->program()->sync($programs);
        }
        return $organisation;
    }

    /**
     * @param mixed $request
     * @param mixed $organisation
     *
     * @return [type]
     */
    public function updateStatus($request, $organisation)
    {
        $organisation->status = $request['status'];
        $organisation->update();
        return $organisation;
    }

    /**
     * Set Organisation Data
     * @param mixed $request
     * @param mixed $organisation
     *
     * @return [collection]
     */
    private function setOrganisation($request, $organisation)
    {
        $organisation->name = $request['name'];
        $organisation->email = $request['email'];
        $organisation->mobile = $request['mobile'] ?? null;
        $organisation->state_id = isset($request['state']) ? ($request['state'] ?: null) : null;
        $organisation->district_id = isset($request['district']) ? ($request['district'] ?: null) : null;
        $organisation->city = $request['city'] ?? null;
        $organisation->address = $request['address'] ?? null;
        $organisation->website = $request['website'] ?? null;
        $organisation->pincode = isset($request['pincode']) ? ($request['pincode'] ?: null) : null;
        $organisation->tenant_id = getTenant();
        return $organisation;
    }

    /**
     * Set Approval Data
     * @param mixed $request
     * @param mixed $centre
     *
     * @return [collection]
     */
    private function setApproval($approval, $organisation)
    {
        $approval->type = Approval::TYPE_SINGLE;
        $approval->title = trans('admin.organisation_approval_created');
        $approval->reference_id = $organisation->id;
        $approval->model = Approval::TYPE_ORGANISATION_MODEL;
        $approval->status = Approval::TYPE_NEEDS_APPROVAL;
        return $approval;
    }

    /**
     * Export organisations
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function exportOrganisation($request)
    {
        $fileName = "organisation_downlods/" . time() . "organisations.csv";
        Excel::store(new OrganisationExport(), $fileName, 's3');
        return generateTempUrl($fileName);
    }

    /**
     * Import organisations
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function importOrganisation($request)
    {
        $approvalConst = $this->approvalConstCreate();
        $import = new OrganisationImport($approvalConst);
        Excel::import($import, $request['organisation_upload_file']);
        return $import->data;
    }

    public function approvalConstCreate()
    {
        $approvalConst = Str::uuid();
        $approval = Approval::where('reference_id', $approvalConst)->first();
        if ($approval) {
            return $this->approvalConstCreate();
        }
        return $approvalConst;
    }
}
