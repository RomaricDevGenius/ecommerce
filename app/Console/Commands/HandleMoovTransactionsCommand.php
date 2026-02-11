<?php

namespace App\Console\Commands;

use App\Models\WaitingTransaction;
use Illuminate\Console\Command;

class HandleMoovTransactionsCommand extends Command
{
    protected $signature = 'moov:handle-transactions';
    protected $description = 'Check Moov Money pending transactions and confirm orders when payment is done';

    public function handle()
    {
        $transactions = WaitingTransaction::query()->limit(20)->get();

        foreach ($transactions as $transaction) {
            $result = handleMoovMoneyPayment($transaction->transaction_id);
            $resultJson = @json_decode($result);

            if ($resultJson && isset($resultJson->status) && (string) $resultJson->status === '0') {
                $paymentDetails = json_encode([
                    'transaction_id' => $transaction->transaction_id,
                    'phone' => $transaction->phone,
                    'method' => 'Moov Money',
                ]);
                checkout_done($transaction->combined_order_id, $paymentDetails);
                $transaction->delete();
            }
        }

        return 0;
    }
}
