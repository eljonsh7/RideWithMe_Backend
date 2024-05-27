<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('friends', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['user1_id']);
            
            // Add the new column
            $table->uuid('user_id')->nullable();
        });

        // Copy data from user1_id to user_id
        DB::statement('UPDATE friends SET user_id = user1_id');

        Schema::table('friends', function (Blueprint $table) {
            // Drop the old column
            $table->dropColumn('user1_id');
            
            // Make the new column not nullable
            $table->uuid('user_id')->nullable(false)->change();
            
            // Recreate the foreign key constraint
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('friends', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['user_id']);
            
            // Add the old column back
            $table->uuid('user1_id')->nullable();
        });

        // Copy data from user_id back to user1_id
        DB::statement('UPDATE friends SET user1_id = user_id');

        Schema::table('friends', function (Blueprint $table) {
            // Drop the new column
            $table->dropColumn('user_id');
            
            // Make the old column not nullable
            $table->uuid('user1_id')->nullable(false)->change();
            
            // Recreate the foreign key constraint
            $table->foreign('user1_id')->references('id')->on('users');
        });
    }
};
