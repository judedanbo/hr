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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('title', 10)->nullable();
            $table->string('surname', 30);
            $table->string('first_name', 60)->nullable();
            $table->string('other_names', 60);
            $table->date('date_of_birth');
            $table->string('gender', 10);
            $table->string('marital_status', 12)->nullable();
            $table->string('social_security_number')->nullable();
            $table->string('national_id_number')->nullable();
            $table->string('nationality', 40)->nullable();
            $table->string('image')->nullable();
            $table->text('about')->nullable();
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
        Schema::dropIfExists('people');
    }
};