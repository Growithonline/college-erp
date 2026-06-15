<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PlaywrightToggle extends Command
{
    protected $signature = 'playwright:toggle {state : on ya off}';
    protected $description = 'Playwright testing mode on/off karo';

    public function handle(): int
    {
        $state = strtolower($this->argument('state'));

        if (!in_array($state, ['on', 'off'])) {
            $this->error('Sirf "on" ya "off" likho.');
            return 1;
        }

        $envPath = base_path('.env');
        $content = file_get_contents($envPath);

        if (!str_contains($content, 'PLAYWRIGHT_TESTING=')) {
            $this->error('PLAYWRIGHT_TESTING .env mein nahi mila. Pehle add karo: PLAYWRIGHT_TESTING=false');
            return 1;
        }

        $newValue = $state === 'on' ? 'true' : 'false';
        $oldValue = $state === 'on' ? 'false' : 'true';

        $updated = str_replace(
            "PLAYWRIGHT_TESTING={$oldValue}",
            "PLAYWRIGHT_TESTING={$newValue}",
            $content
        );

        file_put_contents($envPath, $updated);

        $this->call('config:clear');
        $this->call('config:cache'); // server pe cached config update karo

        $this->info('Playwright testing: ' . strtoupper($state));

        return 0;
    }
}
