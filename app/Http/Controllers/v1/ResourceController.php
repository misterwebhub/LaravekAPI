<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\ResourceRequest;
use App\Http\Requests\v1\UpdateStatusResourceRequest;
use App\Http\Resources\v1\ResourceResource;
use App\Models\Resource;
use App\Repositories\v1\ResourceRepository;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    private $resourceRepository;

    /**
     * @param ResourceRepository $resourceRepository
     */
    public function __construct(ResourceRepository $resourceRepository)
    {
        $this->resourceRepository = $resourceRepository;
        $this->middleware('permission:resource.view', ['only' => ['index']]);
        $this->middleware('permission:resource.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:resource.update', ['only' => ['show', 'update','updateStatus']]);
        $this->middleware('permission:resource.destroy', ['only' => ['destroy']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $resourcees = $this->resourceRepository->index($request->all());
        return ResourceResource::collection($resourcees)->additional(['total_without_filter' => Resource::count()]);
    }

    /**
     * @param ResourceRequest $request
     *
     * @return [type]
     */
    public function store(ResourceRequest $request)
    {
        $resource = $this->resourceRepository->store($request->all());
        return (new ResourceResource($resource))
            ->additional(['message' => trans('admin.resource_added')]);
    }

    /**
     * @param mixed $resource
     *
     * @return [type]
     */
    public function show(Resource $resource)
    {
        return new ResourceResource($resource);
    }

    /**
     * @param ResourceRequest $request
     * @param mixed $resource
     *
     * @return [type]
     */
    public function update(ResourceRequest $request, Resource $resource)
    {
        $resource = $this->resourceRepository->update($request->all(), $resource);
        return (new ResourceResource($resource))
            ->additional(['message' => trans('admin.resource_updated')]);
    }

    /**
     * @param mixed $resource
     *
     * @return [type]
     */
    public function destroy(Resource $resource)
    {
        $this->resourceRepository->destroy($resource);
        return response(['message' => trans('admin.resource_deleted')], 200);
    }

    /**
     * @param Request $request
     * @param mixed $resource
     *
     * @return [type]
     */
    public function updateStatus(UpdateStatusResourceRequest $request, Resource $resource)
    {
        $resource = $this->resourceRepository->updateStatus($request->all(), $resource);
        return (new ResourceResource($resource))
            ->additional(['message' => trans('admin.resource_status_change')]);
    }
}
