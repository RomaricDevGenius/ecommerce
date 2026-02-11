<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('waiting_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('combined_order_id');
            $table->string('phone', 50);
            $table->string('transaction_id');
            $table->timestamps();
            $table->index('combined_order_id');
        });

        $exists = DB::table('payment_methods')->whereIn('name', ['orange', 'moov'])->exists();
        if (!$exists) {
            DB::table('payment_methods')->insert([
                ['name' => 'orange', 'active' => 0, 'addon_identifier' => null, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'moov', 'active' => 0, 'addon_identifier' => null, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('waiting_transactions');
        DB::table('payment_methods')->whereIn('name', ['orange', 'moov'])->delete();
    }
};
