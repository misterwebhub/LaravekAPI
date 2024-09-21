<?php

namespace App\Repositories\v1;

use App\Models\Resource;
use Spatie\QueryBuilder\QueryBuilder;
use Carbon\Carbon;
use App\Services\ResourceSubjectCustomSort;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\Filter\ResourceCustomFilter;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * [Description ResourceRepository]
 */
class ResourceRepository
{
    /**
     * List all resourcees
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request)
    {
        $resourcees = QueryBuilder::for(Resource::class)
            ->allowedFilters([
                'name', 'category', 'status', 'course.id', 'subject.id', 'subject.name',
                AllowedFilter::custom('search_value', new ResourceCustomFilter()),
            ])
            ->allowedSorts(
                [
                    'name', 'status',
                    AllowedSort::custom('subject.name', new ResourceSubjectCustomSort()),
                ]
            )
            ->where('tenant_id', getTenant())
            ->with(['course', 'subject'])
            ->latest()
            ->paginate($request['limit'] ?? null);
        return $resourcees;
    }

    /**
     * Create a new Resource
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request)
    {
        $resource = new Resource();
        $resource->status = Resource::STATUS_ACTIVE;
        $resource = $this->setResource($request, $resource);
        $resource->save();
        return $resource;
    }

    /**
     * Delete a Resource
     * @param mixed $Resource
     *
     * @return [type]
     */
    public function destroy($resource)
    {
        $resource = $this->deleteResource($resource);
        $resource->delete();
    }

    /**
     * Update Resource
     * @param mixed $request
     * @param mixed $Resource
     *
     * @return [json]
     */
    public function update($request, $resource)
    {
        $resource = $this->setResource($request, $resource);
        $resource->update();
        return $resource;
    }

    /**
     * Update status of Resource
     * @param mixed $request
     * @param mixed $resource
     *
     * @return [type]
     */
    public function updateStatus($request, $resource)
    {
        $resource = $this->setStatus($resource, $request);
        $resource->update();
        return $resource;
    }

    /**
     * Set Resource Data
     * @param mixed $request
     * @param mixed $resource
     *
     * @return [collection]
     */
    private function setResource($request, $resource)
    {
        $resource->name = $request['name'];
        $resource->resource_category_id =  isset($request['resource_category']) ?
            ($request['resource_category'] ?: null) : null;
        $resource->category_name = isset($request['category_name']) ? ($request['category_name'] ?: null) : null;
        $resource->link =  $request['link'];
        $resource->point =  isset($request['point']) ? ($request['point'] ?: null) : null;
        $resource->course_id = isset($request['course_id']) ? ($request['course_id'] ?: null) : null;
        $resource->subject_id =  $request['subject_id'];
        $resource->tenant_id = getTenant();
        return $resource;
    }

    /**
     * @param mixed $resource
     * @param mixed $request
     *
     * @return [type]
     */
    private function setStatus($resource, $request)
    {
        switch ($request['status']) {
            case 'inactive':
                $resource->status = Resource::STATUS_INACTIVE;
                break;
            case 'deleted':
                $resource->status = Resource::STATUS_DELETED;
                break;
            case 'active':
            default:
                $resource->status = Resource::STATUS_ACTIVE;
                break;
        }
        return $resource;
    }

    /**
     * Update Resource
     * @param mixed $request
     * @param mixed $Resource
     *
     * @return [json]
     */
    public function deleteResource($resource)
    {
        $resource->status = Resource::STATUS_DELETED;
        $resource->update();
        return $resource;
    }
}
