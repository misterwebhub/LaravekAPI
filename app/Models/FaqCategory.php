<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaqCategory extends Model
{
    use HasFactory;
    use Uuids;

    protected $table = "faq_categories";

    public function subCategories()
    {
        return $this->hasMany(FaqSubCategory::class, 'faq_category_id')->orderBy('name');
    }
}
