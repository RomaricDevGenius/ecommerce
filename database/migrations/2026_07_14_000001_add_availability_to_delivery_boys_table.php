<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_boys', function (Blueprint $table) {
            $table->enum('availability_status', ['available', 'busy', 'offline', 'pause'])
                  ->default('offline')
                  ->after('user_id');
            $table->decimal('lat', 10, 7)->nullable()->after('availability_status');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->timestamp('last_seen_at')->nullable()->after('lng');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_boys', function (Blueprint $table) {
            $table->dropColumn(['availability_status', 'lat', 'lng', 'last_seen_at']);
        });
    }
};
