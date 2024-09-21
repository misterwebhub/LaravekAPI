<?php

namespace App\Http\Resources\v1;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SubjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (!$this->name) {
            return parent::toArray($request);
        }
        $image = "";
        if ($this->image) {
            $image = Storage::disk('s3')->temporaryUrl(
                $this->image,
                Carbon::now()->addMinutes(10)
            );
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tag' => $this->tag,
            'description' => $this->description,
            'order' => $this->order,
            'project_names' => implode(',', array_filter($this->projects->pluck('name')->toArray())),
            'centre_count' => $this->centres()->count() ?? 0,
            'image_url' => generateTempUrl($this->image) ?? "", //;Storage::url($this->image),
              'subject_mandatory' => $this->subject_mandatory, //
            'image_path' => $this->image,
            'status' => $this->status,
            'created_by'=>$this->subject_user,
            'subjectlogs'=>$this->subjectlogs,
            'lastSentonReview'=>$this->lastSentonReview
            
        ];
    }
}
