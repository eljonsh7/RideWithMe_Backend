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
        Schema::disableForeignKeyConstraints();

        Schema::create('routes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('driver_id')->constrained('users');
            $table->foreignUuid('city_from_id')->constrained('cities');
            $table->foreignUuid('city_to_id')->constrained('cities');
            $table->foreignUuid('location_id')->constrained('locations');
            $table->dateTime('datetime');
            $table->integer('passengers_number');
        });

        Schema::enableForeignKeyConstraints();
    }
    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
