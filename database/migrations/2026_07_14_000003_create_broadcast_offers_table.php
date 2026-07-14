<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('broadcast_id');
            $table->foreign('broadcast_id')->references('id')->on('order_broadcasts')->onDelete('cascade');
            $table->unsignedInteger('delivery_boy_id');
            $table->foreign('delivery_boy_id')->references('id')->on('users')->onDelete('cascade');
            $table->decimal('priority_score', 5, 2)->default(0);
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->enum('response', ['pending', 'accepted', 'declined', 'timeout'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_offers');
    }
};
