<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('seguradora:processar-inadimplencia')
    ->dailyAt('21:02') 
    ->withoutOverlapping() 
    ->onOneServer();

Schedule::command('seguradora:processar-renovacoes')
    ->dailyAt('21:03') 
    ->withoutOverlapping() 
    ->onOneServer();


//comando artisan: php artisan schedule:work