<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\FaqRequest;
use App\Http\Resources\v1\FaqResource;
use App\Models\Faq;
use App\Repositories\v1\FaqRepository;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    private $faqRepository;
    /**
     * @param FaqRepository $faqRepository
     */
    public function __construct(FaqRepository $faqRepository)
    {
        $this->faqRepository = $faqRepository;
        $this->middleware('permission:help.view', ['only' => ['index']]);
        $this->middleware('permission:help.create', ['only' => ['store']]);
        $this->middleware('permission:help.update', ['only' => ['edit','update']]);
        $this->middleware('permission:help.destroy', ['only' => ['destroy']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $faqs = $this->faqRepository->index($request->all());
        return FaqResource::collection($faqs)
        ->additional(['total_without_filter' => Faq::count()]);
    }

    /**
     * @param FaqRequest $request
     *
     * @return [type]
     */
    public function store(FaqRequest $request)
    {

        $faq = $this->faqRepository->store($request->all());
        return (new FaqResource($faq))
            ->additional(['message' => trans('admin.faq_added')]);
    }

    /**
     * @param mixed $id
     *
     * @return [type]
     */
    public function edit(Faq $faq)
    {
        return new FaqResource($faq);
    }

    /**
     * @param FaqRequest $request
     * @param mixed $id
     *
     * @return [type]
     */
    public function update(FaqRequest $request, Faq $faq)
    {
        $faqs = $this->faqRepository->update($request->all(), $faq);
        return (new FaqResource($faqs))
            ->additional(['message' => trans('admin.faq_updated')]);
    }

    /**
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy(Faq $faq)
    {
        $this->faqRepository->destroy($faq);
        return response(['message' => trans('admin.faq_deleted')]);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function importFaqs(Request $request)
    {
        $importData = $this->faqRepository->importFaqs($request->all());
        return response([
            'data' => $importData,
        ], 200);
    }
}
