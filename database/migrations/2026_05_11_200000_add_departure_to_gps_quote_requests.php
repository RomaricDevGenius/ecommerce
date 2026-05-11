<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gps_quote_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('seller_id')->nullable()->after('user_id');
            $table->decimal('departure_lat', 17, 15)->nullable()->after('seller_id');
            $table->decimal('departure_lng', 17, 15)->nullable()->after('departure_lat');
        });
    }

    public function down(): void
    {
        Schema::table('gps_quote_requests', function (Blueprint $table) {
            $table->dropColumn(['seller_id', 'departure_lat', 'departure_lng']);
        });
    }
};
