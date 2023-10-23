<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->string('document_type', 3)->nullable();
            $table->string('document_title', 100)->nullable();
            $table->string('document_number', 20)->nullable();
            $table->string('document_file', 100)->comment('file this documents is contained in or part of')->nullable();
            $table->string('file_type', 100)->comment('file type of the document');
            $table->string('file_name', 255)->comment('stored name of the document');
            $table->string('document_status', 3)->nullable();
            $table->string('document_remarks', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents');
    }
};
