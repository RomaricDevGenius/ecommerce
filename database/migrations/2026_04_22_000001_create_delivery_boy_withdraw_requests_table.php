<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryBoyWithdrawRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('delivery_boy_withdraw_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('delivery_boy_id'); // user_id of the delivery boy (matches users.id)
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->nullable();  // e.g. orange_money, moov_money, cash
            $table->string('account_number')->nullable();  // phone/account to receive payment
            $table->text('notes')->nullable();             // delivery boy message
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->text('admin_note')->nullable();        // note when confirming payment
            $table->boolean('viewed')->default(false);     // admin viewed flag
            $table->timestamps();

            $table->foreign('delivery_boy_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['delivery_boy_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_boy_withdraw_requests');
    }
}
