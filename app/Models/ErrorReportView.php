<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorReportView extends Model
{
    use HasFactory;
    use Uuids;

    protected $table = "error_report_view";


    /**
     * Category of the Faq.
     */
    public function category()
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }
    /**
     * SubCategory of the Faq.
     */
    public function subCategory()
    {
        return $this->belongsTo(FaqSubCategory::class, 'faq_sub_category_id');
    }

    /**
     * Type of the Lesson.
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the profile of the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Subject of the Lesson.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
