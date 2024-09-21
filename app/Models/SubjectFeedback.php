<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubjectFeedback extends AppModel
{
    use HasFactory;
    use Uuids;

    protected $table = "subject_feedbacks";
}