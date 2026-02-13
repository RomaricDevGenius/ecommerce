<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('payku_transactions')) {
            return;
        }
        Schema::create('payku_transactions', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('status', 191)->nullable();
            $table->string('order', 191)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('subject', 191)->nullable();
            $table->text('url')->nullable();
            $table->unsignedInteger('amount')->nullable();
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payku_transactions');
    }
};
