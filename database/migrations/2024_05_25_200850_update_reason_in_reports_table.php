<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateReasonInReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'reason')) {
                DB::statement('ALTER TABLE reports DROP COLUMN reason');
            }

            $table->unsignedInteger('reason');

            $table->foreign('reason')
                  ->references('id')
                  ->on('report_reasons')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reports', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['reason']);

            // Drop the uuid reason column
            $table->dropColumn('reason');

            // Add the original reason column back
            $table->string('reason');
        });
    }
}
