<?php

namespace App\Providers;

use App\Models\Approval;
use App\Models\Centre;
use App\Models\Course;
use App\Models\MqopsExternalMeeting;
use App\Models\Organisation;
use App\Models\Phase;
use App\Models\Program;
use App\Models\Project;
use App\Models\Subject;
use App\Observers\CentreObserver;
use App\Models\MqopsInternalMeeting;
use App\Models\MqopsCentreVisit;
use App\Observers\CourseObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Observers\OrganisationObserver;
use App\Observers\PhaseObserver;
use App\Observers\ProgramObserver;
use App\Observers\ProjectObserver;
use App\Observers\SubjectObserver;
use App\Observers\MqopsInternalMeetingObserver;
use App\Observers\MqopsExternalMeetingObserver;
use App\Observers\MqopsCentreVisitObserver;
use App\Observers\ApprovalObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(125);
        Organisation::observe(OrganisationObserver::class);
        Centre::observe(CentreObserver::class);
        Program::observe(ProgramObserver::class);
        Project::observe(ProjectObserver::class);
        Subject::observe(SubjectObserver::class);
        Course::observe(CourseObserver::class);
        Phase::observe(PhaseObserver::class);
        MqopsInternalMeeting::observe(MqopsInternalMeetingObserver::class);
        MqopsExternalMeeting::observe(MqopsExternalMeetingObserver::class);
        MqopsCentreVisit::observe(MqopsCentreVisitObserver::class);
        Approval::observe(ApprovalObserver::class);
    }
}
