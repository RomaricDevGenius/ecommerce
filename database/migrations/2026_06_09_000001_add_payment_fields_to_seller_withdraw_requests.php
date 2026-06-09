<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentFieldsToSellerWithdrawRequests extends Migration
{
    public function up()
    {
        Schema::table('seller_withdraw_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('seller_withdraw_requests', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('amount'); // orange_money, moov_money, coris_money, cash
            }
            if (!Schema::hasColumn('seller_withdraw_requests', 'account_number')) {
                $table->string('account_number')->nullable()->after('payment_method'); // numéro tel/compte où envoyer l'argent
            }
        });
    }

    public function down()
    {
        Schema::table('seller_withdraw_requests', function (Blueprint $table) {
            if (Schema::hasColumn('seller_withdraw_requests', 'account_number')) {
                $table->dropColumn('account_number');
            }
            if (Schema::hasColumn('seller_withdraw_requests', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
}
