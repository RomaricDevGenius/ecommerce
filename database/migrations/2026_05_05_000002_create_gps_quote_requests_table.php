<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('gps_quote_requests');

        Schema::create('gps_quote_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->decimal('delivery_lat', 10, 7);
            $table->decimal('delivery_lng', 10, 7);
            $table->decimal('distance_km', 8, 2);
            // pending → confirmed (admin set amount) → accepted (client ok) | refused | expired
            $table->string('status', 20)->default('pending');
            $table->decimal('supplement_amount', 10, 2)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_quote_requests');
    }
};
