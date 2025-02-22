<?php

namespace WatheqAlshowaiter\BackupTables\Commands;

use Illuminate\Console\Command;
use WatheqAlshowaiter\BackupTables\BackupTables;

class BackupTableCommand extends Command
{
    const SUCCESS = 0;

    const FAILURE = 1;

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

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error backing up table: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
