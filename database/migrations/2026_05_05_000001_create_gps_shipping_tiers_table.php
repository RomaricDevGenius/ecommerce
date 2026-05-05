<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_shipping_tiers', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_km', 8, 2)->default(0);
            $table->decimal('max_km', 8, 2)->nullable(); // NULL = pas de limite (révision manuelle)
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_manual_review')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Paliers par défaut
        \DB::table('gps_shipping_tiers')->insert([
            ['min_km' => 0,  'max_km' => 5,    'price' => 1000, 'is_manual_review' => false, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['min_km' => 5,  'max_km' => 10,   'price' => 1500, 'is_manual_review' => false, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['min_km' => 10, 'max_km' => 20,   'price' => 2000, 'is_manual_review' => false, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['min_km' => 20, 'max_km' => null,  'price' => 0,    'is_manual_review' => true,  'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('gps_shipping_pending')->default(false);
            $table->decimal('gps_distance_km', 8, 2)->nullable();
            $table->decimal('gps_supplement', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_shipping_tiers');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['gps_shipping_pending', 'gps_distance_km', 'gps_supplement']);
        });
    }
};
