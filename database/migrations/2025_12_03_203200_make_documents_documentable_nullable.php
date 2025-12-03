<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('documentable_type')->nullable()->change();
            $table->unsignedBigInteger('documentable_id')->nullable()->change();
            $table->string('file_type', 100)->nullable()->change();
            $table->string('file_name', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('documentable_type')->nullable(false)->change();
            $table->unsignedBigInteger('documentable_id')->nullable(false)->change();
            $table->string('file_type', 100)->nullable(false)->change();
            $table->string('file_name', 255)->nullable(false)->change();
        });
    }
};
