<?php

use App\Jobs\TestJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('queue:test', function () {
    // Dispatch a test job
    TestJob::dispatch('This is a test job!');
    $this->info('Test job has been queued!');
    $this->info('Run "php artisan queue:work" to process the job.');
})->purpose('Dispatch a test job to the queue');
