<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaqSubCategory extends Model
{
    use HasFactory, Uuids;
    protected $table="faq_sub_categories";
}