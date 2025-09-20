<?php

namespace WatheqAlshowaiter\BackupTables\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use WatheqAlshowaiter\BackupTables\BackupTables;

class BackupTableCommand extends Command
{
    const SUCCESS = 0;

    const FAILURE = 1;

    const STAR_PROMPT_CACHE_KEY = 'backup-tables.github_star_prompted';

    protected $signature = 'backup:tables {targets* : The table names or model classes to backup (space-separated)}';

    protected $description = 'Backup a specific database table/s based on provided table names or model classes';

    public function handle()
    {
        $tables = $this->argument('targets');

        try {
            $result = BackupTables::generateBackup($tables);

            if (! $result) {
                $this->error('Failed to backup table.');

                return self::FAILURE;
            }

            $this->askToStarRepository();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error backing up table: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Ask user to star the GitHub repository for only the first time he uses the package in terminal
     *
     * @return void
     */
    private function askToStarRepository()
    {
        if (! $this->input->isInteractive()) {
            return;
        }

        $cacheKey = self::STAR_PROMPT_CACHE_KEY;
        $repo = 'https://github.com/WatheqAlshowaiter/backup-tables';

        if (Cache::get($cacheKey)) {
            return;
        }

        $wantsToStar = $this->confirm(
            '🌟 Help other developers find this package by starring it on GitHub?'
        );

        if ($wantsToStar) {
            $this->openUrl($repo);
            $this->info('Thank you!');
        }

        Cache::forever($cacheKey, true);
    }

    /**
     * @return void
     */
    protected function openUrl(string $url)
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            $command = 'open';
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $command = 'start';
        } else {
            $command = 'xdg-open';
        }

        exec(sprintf('%s %s', $command, escapeshellarg($url)));
    }
}
