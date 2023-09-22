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
        Schema::create('job_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained();
            $table->foreignId('staff_id')->references('id')->on('institution_person');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('remarks', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['job_id', 'staff_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_staff');
    }
};