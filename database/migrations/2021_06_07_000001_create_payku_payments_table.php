<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('payku_payments')) {
            return;
        }
        Schema::create('payku_payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 191)->nullable();
            $table->string('order_id', 191)->nullable();
            $table->unsignedInteger('amount')->nullable();
            $table->string('status', 191)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payku_payments');
    }
};
