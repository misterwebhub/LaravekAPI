<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;


class UpdatePersonalAccessToken implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public function __construct($personalAccessToken, $newAttributes)
    {
        $this->personalAccessToken = $personalAccessToken;
        $this->newAttributes = $newAttributes;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::table($this->personalAccessToken->getTable())
        ->where('id', $this->personalAccessToken->id)
        ->update($this->newAttributes);
    }
}
