<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('pending_image')->nullable()->after('image');
            $table->timestamp('pending_image_at')->nullable()->after('pending_image');
            $table->foreignId('image_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('pending_image_at');
            $table->timestamp('image_approved_at')->nullable()->after('image_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropConstrainedForeignId('image_approved_by');
            $table->dropColumn(['pending_image', 'pending_image_at', 'image_approved_at']);
        });
    }
};
