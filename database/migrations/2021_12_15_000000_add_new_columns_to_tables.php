<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('payku_payments')) {
            if (!Schema::hasColumn('payku_payments', 'payment_key')) {
                Schema::table('payku_payments', function (Blueprint $table) {
                    $table->string('payment_key', 191)->nullable();
                });
            }
            if (!Schema::hasColumn('payku_payments', 'transaction_key')) {
                Schema::table('payku_payments', function (Blueprint $table) {
                    $table->string('transaction_key', 191)->nullable();
                });
            }
            if (!Schema::hasColumn('payku_payments', 'deposit_date')) {
                Schema::table('payku_payments', function (Blueprint $table) {
                    $table->dateTime('deposit_date')->nullable();
                });
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('payku_payments')) {
            Schema::table('payku_payments', function (Blueprint $table) {
                $columns = ['payment_key', 'transaction_key', 'deposit_date'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('payku_payments', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
