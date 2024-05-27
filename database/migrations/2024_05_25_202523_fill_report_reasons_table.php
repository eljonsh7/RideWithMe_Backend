<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ReportReason;

class FillReportReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $reasons = [
            'Unsafe driving behavior',
            'Inappropriate behavior',
            'Fraudulent activity',
            'Harassment',
            'Failure to comply with safety regulations',
            'Reported vehicle cleanliness',
            'Violating community guidelines',
            'Unruly behavior',
            'Being intoxicated or under the influence of drugs',
            'Refusing to follow safety instructions',
            'Non-payment or fare evasion',
            'Disruptive behavior',
            'Vandalism or damage to the vehicle',
            'Making false accusations against the driver',
        ];

        foreach ($reasons as $reason) {
            ReportReason::create(['reason' => $reason]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove the inserted data if needed
        ReportReason::truncate();
    }
}
