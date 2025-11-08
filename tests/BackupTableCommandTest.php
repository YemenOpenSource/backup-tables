<?php

namespace WatheqAlshowaiter\BackupTables\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use WatheqAlshowaiter\BackupTables\Commands\BackupTableCommand;
use WatheqAlshowaiter\BackupTables\Tests\Models\Father;
use WatheqAlshowaiter\BackupTables\Tests\Models\Mother;
use WatheqAlshowaiter\BackupTables\Tests\Models\Temp;

class BackupTableCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forever(BackupTableCommand::STAR_PROMPT_CACHE_KEY, true);
    }

    protected function tearDown(): void
    {
        Cache::forget(BackupTableCommand::STAR_PROMPT_CACHE_KEY);
        parent::tearDown();
    }

    /** @test */
    public function it_can_backup_a_table()
    {
        $now = now();

        $this->artisan('backup:tables', ['targets' => 'temps'])
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $backupTablePattern = 'temps_backup_'.$now->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($backupTablePattern));

        Schema::dropIfExists($backupTablePattern);
    }

    /** @test */
    public function it_can_backup_a_table_by_model_class()
    {
        $now = now();

        $this->artisan(BackupTableCommand::class, ['targets' => Temp::class])
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $backupTablePattern = 'temps_backup_'.$now->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($backupTablePattern));
        Schema::dropIfExists($backupTablePattern);
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
        $now = now();
        $tables = ['temps', 'fathers'];

        $this->artisan('backup:tables', ['targets' => $tables])
            ->assertExitCode(BackupTableCommand::SUCCESS);

        foreach ($tables as $table) {
            $backupTablePattern = $table.'_backup_'.$now->format('Y_m_d_H_i_s');

            $this->assertTrue(Schema::hasTable($backupTablePattern));
            Schema::dropIfExists($backupTablePattern);
        }
    }

    /** @test */
    public function it_can_backup_multiple_models()
    {
        $models = [Father::class, Mother::class];
        $now = now();

        $this->artisan('backup:tables', ['targets' => $models])
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $backupTablePattern1 = 'fathers_backup_'.$now->format('Y_m_d_H_i_s');
        $backupTablePattern2 = 'mothers_backup_'.$now->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($backupTablePattern1));
        $this->assertTrue(Schema::hasTable($backupTablePattern2));

        Schema::dropIfExists($backupTablePattern1);
        Schema::dropIfExists($backupTablePattern2);
    }

    /** @test */
    public function it_fails_when_any_table_does_not_exist_but_saves_corrected_tables()
    {
        Schema::dropIfExists('existing_table');
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

        Schema::dropIfExists($backupExistingTablePattern);
        Schema::dropIfExists('existing_table');
    }

    public function test_does_not_ask_if_already_cached()
    {
        Cache::forever(BackupTableCommand::STAR_PROMPT_CACHE_KEY, true); // make it clear here

        $now = now();

        $this->artisan('backup:tables', ['targets' => 'fathers'])
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $this->assertTrue(Cache::get(BackupTableCommand::STAR_PROMPT_CACHE_KEY));

        // Cleanup
        $backupTablePattern = 'fathers_backup_'.$now->format('Y_m_d_H_i_s');
        Schema::dropIfExists($backupTablePattern);
    }

    public function test_asks_and_user_declines()
    {
        $this->markTestSkipped('WIP');
        Cache::forget(BackupTableCommand::STAR_PROMPT_CACHE_KEY);

        $now = now();

        $this->artisan('backup:tables', ['targets' => 'fathers'])
            ->expectsQuestion('🌟 Help other developers find this package by starring it on GitHub?', false)
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $this->assertTrue(Cache::get(BackupTableCommand::STAR_PROMPT_CACHE_KEY));

        // Cleanup
        $backupTablePattern = 'fathers_backup_'.$now->format('Y_m_d_H_i_s');
        Schema::dropIfExists($backupTablePattern);
    }

    public function test_asks_and_user_accepts()
    {
        Cache::forget(BackupTableCommand::STAR_PROMPT_CACHE_KEY);

        // Create a subclass that overrides openUrl()
        $stubCommand = new class extends BackupTableCommand {
            public string $calledWith = '';

            // Change to protected so test can inspect
            protected function openUrl(string $url): void
            {
                // Do NOT actually exec() — just record that it was called
                $this->calledWith = $url;
            }
        };

        // Replace the command in Laravel's container so artisan uses our stub
        $this->app->extend(BackupTableCommand::class, fn() => $stubCommand);

        $now = now();

        $this->artisan('backup:tables', ['targets' => 'temps'])
            ->expectsQuestion('🌟 Help other developers find this package by starring it on GitHub?', true)
            ->expectsOutput('Thank you!')
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $this->assertStringContainsString('https://github.com/WatheqAlshowaiter/backup-tables',
            $stubCommand->calledWith);

        // Cleanup
        $backupTablePattern = 'temps_backup_'.$now->format('Y_m_d_H_i_s');
        Schema::dropIfExists($backupTablePattern);
    }
}
