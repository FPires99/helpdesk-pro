<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetDemo extends Command
{
    protected $signature = 'helpdesk:reset-demo {--force : Run without asking}';

    protected $description = 'Wipe the database and reload the demo company (scheduled nightly in demo mode)';

    public function handle(): int
    {
        if (! config('helpdesk.demo')) {
            $this->error('Demo mode is off (HELPDESK_DEMO=false); refusing to wipe real data.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This deletes ALL data and reloads the demo. Continue?')) {
            return self::SUCCESS;
        }

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->info('Demo data reset.');

        return self::SUCCESS;
    }
}
