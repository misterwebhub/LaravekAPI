<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Faq extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
        'tenant_id'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Faq {$eventName}")
            ->useLogName('Faq')
            ->logOnlyDirty();
    }
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
}
