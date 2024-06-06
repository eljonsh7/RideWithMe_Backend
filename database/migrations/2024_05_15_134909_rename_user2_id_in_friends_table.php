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
            $table->dropForeign(['user2_id']);
            
            // Add the new column
            $table->uuid('friend_id')->nullable();
        });

        // Copy data from user2_id to friend_id
        DB::statement('UPDATE friends SET friend_id = user2_id');

        Schema::table('friends', function (Blueprint $table) {
            // Drop the old column
            $table->dropColumn('user2_id');
            
            // Make the new column not nullable
            $table->uuid('friend_id')->nullable(false)->change();
            
            // Recreate the foreign key constraint
            $table->foreign('friend_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('friends', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['friend_id']);
            
            // Add the old column back
            $table->uuid('user2_id')->nullable();
        });

        // Copy data from friend_id back to user2_id
        DB::statement('UPDATE friends SET user2_id = friend_id');

        Schema::table('friends', function (Blueprint $table) {
            // Drop the new column
            $table->dropColumn('friend_id');
            
            // Make the old column not nullable
            $table->uuid('user2_id')->nullable(false)->change();
            
            // Recreate the foreign key constraint
            $table->foreign('user2_id')->references('id')->on('users');
        });
    }
};
