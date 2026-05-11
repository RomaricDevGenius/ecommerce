<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gps_quote_requests', function (Blueprint $table) {
            $table->decimal('base_amount', 10, 2)->default(0)->after('distance_km');
        });
    }

    public function down(): void
    {
        Schema::table('gps_quote_requests', function (Blueprint $table) {
            $table->dropColumn('base_amount');
        });
    }
};
