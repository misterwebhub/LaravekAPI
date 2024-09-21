<?php

namespace App\Jobs;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ErrorResolved implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $errorReport;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Report $errorReport)
    {
        $this->errorReport = $errorReport;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 
    }
}
