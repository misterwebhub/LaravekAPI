<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Repositories\v1\CentreMergeRepository;
use App\Http\Requests\v1\CentreMergeRequest;
use App\Models\Centre;

class CentreMergeController extends Controller
{
    private $centreMergeRepository;
    /**
     * @param StudentMergeRepository $centreMergeRepository
     */
    public function __construct(CentreMergeRepository $centreMergeRepository)
    {
        $this->centreMergeRepository = $centreMergeRepository;
        $this->middleware('permission:centre.merge', ['only' => ['centreMerge']]);
    }
    /**
     * @param StudentMergeRequest $request
     *
     * @return [json]
     */
    public function centreMerge(CentreMergeRequest $request)
    {
        $this->authorize('update', Centre::find($request->from_centre));
        $data = $this->centreMergeRepository->centreMerge($request->all());
        return $data;
    }
}
