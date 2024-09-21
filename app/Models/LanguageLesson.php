<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LanguageLesson extends Model
{
    use HasFactory;
    use Notifiable;
    use Uuids;
    protected $table="language_lesson";
}