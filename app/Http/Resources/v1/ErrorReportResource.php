<?php

namespace App\Http\Resources\v1;

use App\Models\Report;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ErrorReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $status = $this->status ?? null;
        if (!is_null($status)) {
            if ($status === REPORT::TYPE_OPEN) {
                $status = trans('admin.open');
            } elseif ($status === REPORT::TYPE_CLOSED) {
                $status = trans('admin.closed');
            } elseif ($status === REPORT::TYPE_REOPENED) {
                $status = trans('admin.reopened');
            } elseif ($status === REPORT::TYPE_PENDING) {
                $status = trans('admin.pending');
            }
        }

        $profile = $this->user->type ?? null;
        if ($profile === REPORT::TYPE_FACILITATOR) {
            $profile = trans('admin.facilitator');
        } elseif ($profile === REPORT::TYPE_STUDENT) {
            $profile = trans('admin.student');
        }
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name ?? null,
            'contact' => $this->mobile ?? null,
            'email' => $this->email ?? null,
            'content' => $this->content ?? null,
            'profile' => $profile,
            'category_id' => $this->faq_category_id ?? null,
            'centre_id' => $this->user_centre_id ?? null,
            'organisation' => $this->organisation_name ?? null,
            'batch' => $this->batch_name ?? null,
            'centre' => $this->centre_name ?? null,
            'state' => $this->user_state ?? null,
            'category_name' => $this->category_name ?? null,
            'sub_category_id' => $this->faq_sub_category_id ?? null,
            'sub_category_name' => $this->subcategory_name ?? null,
            'lesson_id' => $this->lesson_id ?? null,
            'lesson' => $this->lesson_name ?? null,
            'toolkit_id' => $this->subject_id ?? null,
            'toolkit' => $this->subject_name  ?? null,
            'date' => $this->created_at ?? null,
            'resolution_date' => (isset($this->resolution_date) && $this->resolution_date) ?
                Carbon::parse($this->resolution_date)->format('d-m-Y') : null,
            'date_on' => isset($this->created_at) ?
                ($this->created_at->format(config('app.date_format')) ?: null) : null,
            'status' => $status,
            'file_path' => $this->url,
            'url' => ($this->url) ? generateTempUrl($this->url) : null,
            'issue_severity' => $this->issue_severity,
            'resolved_by' => $this->resolved_by ?? null,

        ];
    }
}
