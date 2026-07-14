<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id')->index();
            $table->decimal('radius_km', 5, 1)->default(5.0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['pending', 'assigned', 'failed', 'expired'])->default('pending');
            $table->integer('assigned_to')->nullable()->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_broadcasts');
    }
};
