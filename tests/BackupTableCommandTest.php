<?php

namespace WatheqAlshowaiter\BackupTables\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
    }

    /** @test */
    public function it_can_backup_a_table_by_classname()
    {
        $now = now();
        Schema::create('test_table', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->artisan(BackupTableCommand::class, ['targets' => 'test_table'])
            ->assertExitCode(BackupTableCommand::SUCCESS);

        $backupTablePattern = 'test_table_backup_'.$now->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($backupTablePattern));
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
    }

    /** @test */
    public function it_fails_when_any_table_does_not_exist_but_saves_corrected_tables()
    {
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
    }
}
