<?php

namespace WatheqAlshowaiter\BackupTables\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use WatheqAlshowaiter\BackupTables\Commands\BackupTableCommand;
use WatheqAlshowaiter\BackupTables\Tests\Models\Father;
use WatheqAlshowaiter\BackupTables\Tests\Models\Mother;

class BackupTableCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_backup_a_table()
    {
        Cache::forever('backup-tables.banner_shown', true);

        $now = now();

        Schema::create('test_table', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->artisan('backup:tables', ['targets' => 'test_table'])
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $backupTablePattern = 'test_table_backup_'.$now->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($backupTablePattern));

        Cache::forget('backup-tables.banner_shown');
    }

    /** @test */
    public function it_can_backup_a_table_by_model_class()
    {
        Cache::forever('backup-tables.banner_shown', true);

        $now = now();
        Schema::create('test_table', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });

        $testModelClass = new class extends Model
        {
            protected $table = 'test_table';
        };

        $this->artisan(BackupTableCommand::class, ['targets' => get_class($testModelClass)])
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $backupTablePattern = 'test_table_backup_'.$now->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($backupTablePattern));

        Cache::forget('backup-tables.banner_shown');
    }

    /** @test */
    public function it_fails_when_table_does_not_exist()
    {
        $this->artisan('backup:tables', ['targets' => 'non_existent_table'])
            ->assertExitCode(BackupTableCommand::FAILURE);
    }

    /** @test */
    public function it_can_backup_multiple_tables()
    {
        Cache::forever('backup-tables.banner_shown', true);

        $now = now();
        $tables = ['test_table_1', 'test_table_2'];

        foreach ($tables as $table) {
            Schema::create($table, function ($table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->timestamps();
            });
        }

        $this->artisan('backup:tables', ['targets' => $tables])
            ->assertExitCode(BackupTableCommand::SUCCESS);

        foreach ($tables as $table) {
            $backupTablePattern = $table.'_backup_'.$now->format('Y_m_d_H_i_s');

            $this->assertTrue(Schema::hasTable($backupTablePattern));
        }

        Cache::forget('backup-tables.banner_shown');
    }

    /** @test */
    public function it_can_backup_multiple_models()
    {
        Cache::forever('backup-tables.banner_shown', true);

        $models = [Father::class, Mother::class];
        $now = now();

        $this->artisan('backup:tables', ['targets' => $models])
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $backupTablePattern1 = 'fathers_backup_'.$now->format('Y_m_d_H_i_s');
        $backupTablePattern2 = 'mothers_backup_'.$now->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($backupTablePattern1));

        $this->assertTrue(Schema::hasTable($backupTablePattern2));

        Cache::forget('backup-tables.banner_shown');
    }

    /** @test */
    public function it_fails_when_any_table_does_not_exist_but_saves_corrected_tables()
    {
        Cache::forever('backup-tables.banner_shown', true);

        $now = now();

        Schema::create('existing_table', function ($table) {
            $table->bigIncrements('id');
            $table->timestamps();
        });

        $this->artisan('backup:tables', ['targets' => 'existing_table', 'non_existent_table'])
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $backupExistingTablePattern = 'existing_table_backup_'.$now->format('Y_m_d_H_i_s');
        $backupNonExistingTablePattern = 'non_existent_table_backup_'.$now->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($backupExistingTablePattern));

        $this->assertFalse(Schema::hasTable($backupNonExistingTablePattern));

        Cache::forget('backup-tables.banner_shown');
    }

    public function test_does_not_ask_if_already_cached()
    {
        Cache::forever('backup-tables.banner_shown', true);

        Schema::create('test_table', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->artisan('backup:tables', ['targets' => 'test_table'])
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $this->assertTrue(Cache::get('backup-tables.banner_shown'));

        Cache::forget('model-fields.banner_shown');
    }

    public function test_asks_and_user_declines()
    {
        Cache::forget('backup-tables.banner_shown');

        Schema::create('test_table', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->artisan('backup:tables', ['targets' => 'test_table'])
            ->expectsQuestion('🌟 Help other developers find this package by starring it on GitHub?', false)
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $this->assertTrue(Cache::get('backup-tables.banner_shown'));
        Cache::forget('backup-tables.banner_shown');
    }

    public function test_asks_and_user_accepts()
    {
        Cache::forget('backup-tables.banner_shown');

        // Create a subclass that overrides openUrl()
        $stubCommand = new class extends BackupTableCommand
        {
            public string $calledWith = '';

            // Change to protected so test can inspect
            protected function openUrl(string $url): void
            {
                // Do NOT actually exec() — just record that it was called
                $this->calledWith = $url;
            }
        };

        // Replace the command in Laravel's container so artisan uses our stub
        $this->app->extend(BackupTableCommand::class, fn () => $stubCommand);

        Schema::create('test_table', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->artisan('backup:tables', ['targets' => 'test_table'])
            ->expectsQuestion('🌟 Help other developers find this package by starring it on GitHub?', true)
            ->expectsOutput('Thank you!')
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $this->assertStringContainsString('https://github.com/WatheqAlshowaiter/backup-tables', $stubCommand->calledWith);

        Cache::forget('backup-tables.banner_shown');
    }
}
