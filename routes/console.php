<?php

use Illuminate\Support\Facades\Schedule;

// On the public demo, start every day with fresh demo data.
if (config('helpdesk.demo')) {
    Schedule::command('helpdesk:reset-demo --force')->dailyAt('03:00');
}
