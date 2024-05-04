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
        Schema::table('conversations', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['recipient_id']);

            // Modify the column to be a UUID
            $table->uuid('recipient_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreignUuid('recipient_id')->constrained('users');
        });
    }
};
