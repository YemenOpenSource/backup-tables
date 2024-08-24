<?php

namespace WatheqAlshowaiter\BackupTables\Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use WatheqAlshowaiter\BackupTables\BackupTables;
use WatheqAlshowaiter\BackupTables\Constants;
use WatheqAlshowaiter\BackupTables\Models\Father;
use WatheqAlshowaiter\BackupTables\Models\Mother;
use WatheqAlshowaiter\BackupTables\Models\Son;

class BackupTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_when_table_is_not_correct()
    {
        dump([1 => __FUNCTION__]);
        $tableName = 'not_correct_table_name';

        $this->assertFalse(BackupTables::generateBackup($tableName));
    }

    public function test_return_when_table_string_empty()
    {
        dump([2 => __FUNCTION__]);

        $emptyString = '';
        $emptyArray = [];

        $this->assertFalse(BackupTables::generateBackup($emptyString));
        $this->assertFalse(BackupTables::generateBackup($emptyArray));
    }

    public function test_generate_single_table_backup()
    {
        dump([3 => __FUNCTION__]);

        $dateTime = Carbon::parse("2024-01-01 12:12:08");
        Carbon::setTestNow($dateTime);

        $tableName = 'fathers';
        BackupTables::generateBackup($tableName);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));

        $backupData = DB::table($newTableName)->get();
        dump([
            DB::getDriverName(),
            $backupData
        ]);

        $this->assertEquals(DB::table($tableName)->value('first_name'), DB::table($newTableName)->value('first_name'));
        $this->assertEquals(DB::table($tableName)->value('email'), DB::table($newTableName)->value('email'));

        if (DB::getDriverName() == 'mysql' || DB::getDriverName() == 'mariadb' || (float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT) {
            $this->assertEquals(DB::table($tableName)->value('full_name'), DB::table($newTableName)->value('full_name')); // StoredAs tables
        }

        Carbon::setTestNow();
    }


    public function test_generate_single_table_backup_with_different_data()
    {
        dump([4=> __FUNCTION__]);

        Carbon::setTestNow();
        $tableName = 'mothers';

        Mother::create([
            'types' => 'one',
            'uuid' => Str::uuid(),
            'ulid' => '01J5Y93TVJRVFCSRQFHHF2NRC4',
            'description' => "{ar: 'some description'}"
        ]);

        BackupTables::generateBackup($tableName);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');

        // todo Debugging output to inspect the contents of the backup table
        $backupData = DB::table($newTableName)->get();
        dump([
            DB::getDriverName(),
            $backupData
        ]);

        $this->assertTrue(Schema::hasTable($newTableName));

        $this->assertEquals(DB::table($tableName)->value('types'), DB::table($newTableName)->value('types'));
        $this->assertEquals(DB::table($tableName)->value('uuid'), DB::table($newTableName)->value('uuid'));
        $this->assertEquals(DB::table($tableName)->value('ulid'), DB::table($newTableName)->value('ulid'));
        $this->assertEquals(DB::table($tableName)->value('description'), DB::table($newTableName)->value('description'));

    }

    public function test_generate_single_table_backup_then_another_table_backup_later()
    {
        dump([5 => __FUNCTION__]);

        $dateTime = Carbon::parse("2024-01-01 12:12:08");
        Carbon::setTestNow($dateTime);

        $fatherTable = 'fathers';
        $sonTable = 'sons';

        $father = Father::create([
            'first_name' => 'Ahmed',
            'last_name' => 'Saleh',
            'email' => 'father@email.com',
        ]);

        Son::create([
            'father_id' => $father->id,
        ]);

        BackupTables::generateBackup($fatherTable);

        $currentDateTime = now()->format('Y_m_d_H_i_s');
        $newFatherTable =  $fatherTable . '_backup_' . $currentDateTime;
        $newSonTable = $sonTable . '_backup_' . $currentDateTime;

        // todo Debugging output to inspect the contents of the backup table
        $backupData = DB::table($fatherTable)->get();
        dump([
            DB::getDriverName(),
            $backupData
        ]);

        $this->assertTrue(Schema::hasTable($newFatherTable));

        $this->assertEquals(DB::table('fathers')->value('first_name'), DB::table($newFatherTable)->value('first_name'));
        $this->assertEquals(DB::table('fathers')->value('email'), DB::table($newFatherTable)->value('email'));

        BackupTables::generateBackup($sonTable);

        // todo Debugging output to inspect the contents of the backup table
        $backupData = DB::table($newSonTable)->get();
        dump([
            DB::getDriverName(),
            $backupData
        ]);

        $this->assertTrue(Schema::hasTable($newSonTable));
        $this->assertEquals(DB::table('sons')->value('father_id'), DB::table($newSonTable)->value('father_id'));
        Carbon::setTestNow();
    }

    public function test_generate_multiple_table_backup()
    {
        dump([6=> __FUNCTION__]);

        $dateTime = Carbon::parse("2024-01-01 12:12:08");
        Carbon::setTestNow($dateTime);

        $tableName = 'fathers';
        $tableName2 = 'sons';

        Father::create([
            'id' => 1,
            'first_name' => 'Ahmed',
            'last_name' => 'Saleh',
            'email' => 'father@email.com',
        ]);

        Son::create([
            'father_id' => Father::value('id')
        ]);

        BackupTables::generateBackup([$tableName, $tableName2]);

        $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');
        $newTableName2 = $tableName2.'_backup_'.now()->format('Y_m_d_H_i_s');

        $this->assertTrue(Schema::hasTable($newTableName));
        $this->assertTrue(Schema::hasTable($newTableName2));

        $this->assertEquals(DB::table($tableName)->value('first_name'), DB::table($newTableName)->value('first_name'));
        $this->assertEquals(DB::table($tableName)->value('email'), DB::table($newTableName)->value('email'));

        if (DB::getDriverName() == 'mysql' || DB::getDriverName() == 'mariadb' || (float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT) {
            $this->assertEquals(DB::table($tableName)->value('full_name'), DB::table($newTableName)->value('full_name')); // StoredAs tables
        }

        $this->assertEquals(DB::table($tableName2)->value('father_id'), DB::table($newTableName2)->value('father_id')); // foreign key

        Carbon::setTestNow();
    }



    public function test_generate_single_table_backup_with_with_custom_format()
    {
        dump([7 => __FUNCTION__]);

        $dateTime = Carbon::parse("2024-01-01 12:12:08");
        Carbon::setTestNow($dateTime);

        $tableName = 'fathers';
        $customFormat = 'Y_d_m_H_i';

        BackupTables::generateBackup($tableName, $customFormat);

        $newTableName = $tableName.'_backup_'.now()->format($customFormat);

        $this->assertTrue(Schema::hasTable($newTableName));
        Carbon::setTestNow();

    }

    //public function test_generate_multiple_models_backup()
    //{
//dump([8 => __FUNCTION__]);

//$dateTime = Carbon::parse("2024-01-01 12:12:08");
//Carbon::setTestNow($dateTime);
    //    $tableName = Father::class;
    //    $tableName2 = Son::class;
    //
    //    Father::create([
    //        'id' => 1,
    //        'first_name' => 'Ahmed',
    //        'last_name' => 'Saleh',
    //        'email' => 'father@email.com',
    //    ]);
    //
    //    Son::create([
    //        'father_id' => Father::value('id')
    //    ]);
    //
    //    BackupTables::generateBackup([$tableName, $tableName2]);
    //
    //    $tableName = BackupTables::convertModelToTableName($tableName);
    //    $tableName2 = BackupTables::convertModelToTableName($tableName2);
    //
    //    $newTableName = $tableName.'_backup_'.now()->format('Y_m_d_H_i_s');
    //    $newTableName2 = $tableName2.'_backup_'.now()->format('Y_m_d_H_i_s');
    //
    //    $this->assertTrue(Schema::hasTable($newTableName));
    //    $this->assertTrue(Schema::hasTable($newTableName2));
    //
    //    $this->assertEquals(DB::table($tableName)->value('first_name'), DB::table($newTableName)->value('first_name'));
    //    $this->assertEquals(DB::table($tableName)->value('email'), DB::table($newTableName)->value('email'));
    //
    //    if (DB::getDriverName() == 'mysql' || DB::getDriverName() == 'mariadb' || (float) App::version() >= Constants::VERSION_AFTER_STORED_AS_VIRTUAL_AS_SUPPORT) {
    //        $this->assertEquals(DB::table($tableName)->value('full_name'), DB::table($newTableName)->value('full_name')); // StoredAs tables
    //    }
    //
    //    $this->assertEquals(DB::table($tableName2)->value('father_id'), DB::table($newTableName2)->value('father_id')); // foreign key
    //}

}
