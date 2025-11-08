<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @noinspection PhpIllegalPsrClassPathInspection */
class CreateTempsTable extends Migration
{
    public function up(): void
    {
        Schema::create('temps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temps');
    }
}
