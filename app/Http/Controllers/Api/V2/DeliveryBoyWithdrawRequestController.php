<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\DeliveryBoyWithdrawResource;
use App\Models\DeliveryBoy;
use App\Models\DeliveryBoyWithdrawRequest;
use Illuminate\Http\Request;

class DeliveryBoyWithdrawRequestController extends Controller
{
    /**
     * List the authenticated delivery boy's withdrawal requests.
     */
    public function index()
    {
        $requests = DeliveryBoyWithdrawRequest::where('delivery_boy_id', auth()->id())
            ->latest()
            ->paginate(15);

        return DeliveryBoyWithdrawResource::collection($requests);
    }

    /**
     * Return the earnings summary (balance + minimum withdraw amount).
     */
    public function summary()
    {
        $deliveryBoy = DeliveryBoy::where('user_id', auth()->id())->first();

        if (!$deliveryBoy) {
            return $this->failed(translate('Delivery boy profile not found.'));
        }

        $minimum     = (float) (get_setting('minimum_delivery_boy_withdraw_amount') ?? 500);
        $balance     = (float) $deliveryBoy->total_earning;
        $hasPending  = DeliveryBoyWithdrawRequest::where('delivery_boy_id', auth()->id())
                            ->whereIn('status', ['pending', 'approved'])
                            ->exists();

        // Build withdrawal methods from active payment methods
        $methodDefs = [
            ['value' => 'orange_money', 'label' => 'Orange Money', 'pm_name' => 'orange'],
            ['value' => 'moov_money',   'label' => 'Moov Money',   'pm_name' => 'moov'],
            ['value' => 'coris_money',  'label' => 'Coris Money',  'pm_name' => 'coris'],
        ];

        $withdrawalMethods = [];
        foreach ($methodDefs as $def) {
            $active = \App\Models\PaymentMethod::where('name', $def['pm_name'])
                ->where('active', 1)
                ->exists();
            if ($active) {
                $withdrawalMethods[] = [
                    'value'     => $def['value'],
                    'label'     => $def['label'],
                    'image_url' => static_asset('assets/img/cards/' . $def['pm_name'] . '.png'),
                ];
            }
        }
        $withdrawalMethods[] = [
            'value'     => 'cash',
            'label'     => translate('Cash'),
            'image_url' => null,
        ];

        return response()->json([
            'result'                     => true,
            'balance'                    => $balance,
            'balance_formatted'          => format_price($balance),
            'minimum_withdraw'           => $minimum,
            'minimum_withdraw_formatted' => format_price($minimum),
            'has_pending_request'        => $hasPending,
            'can_withdraw'               => $balance >= $minimum && !$hasPending,
            'withdrawal_methods'         => $withdrawalMethods,
        ]);
    }

    /**
     * Submit a new withdrawal request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:orange_money,moov_money,coris_money,cash',
            'account_number' => 'required_unless:payment_method,cash|nullable|string|max:50',
            'notes'          => 'nullable|string|max:500',
        ]);

        $deliveryBoy = DeliveryBoy::where('user_id', auth()->id())->first();

        if (!$deliveryBoy) {
            return $this->failed(translate('Delivery boy profile not found.'));
        }

        $minimum = (float) (get_setting('minimum_delivery_boy_withdraw_amount') ?? 500);

        if ($request->amount < $minimum) {
            return $this->failed(
                translate('Minimum withdrawal amount is') . ' ' . format_price($minimum)
            );
        }

        if ($request->amount > $deliveryBoy->total_earning) {
            return $this->failed(translate('Insufficient balance.'));
        }

        // Reject if there is already an active request
        $hasPending = DeliveryBoyWithdrawRequest::where('delivery_boy_id', auth()->id())
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($hasPending) {
            return $this->failed(translate('You already have a pending withdrawal request.'));
        }

        $withdrawRequest = DeliveryBoyWithdrawRequest::create([
            'delivery_boy_id' => auth()->id(),
            'amount'          => $request->amount,
            'payment_method'  => $request->payment_method,
            'account_number'  => $request->account_number,
            'notes'           => $request->notes,
            'status'          => 'pending',
            'viewed'          => false,
        ]);

        // Notify admins (via Firebase if configured)
        $this->notifyAdmins($withdrawRequest);

        return response()->json([
            'result'  => true,
            'message' => translate('Withdrawal request submitted successfully.'),
            'data'    => new DeliveryBoyWithdrawResource($withdrawRequest),
        ], 201);
    }

    private function notifyAdmins($withdrawRequest): void
    {
        if (!get_setting('google_firebase')) {
            return;
        }

        try {
            $admins = \App\Models\User::where('user_type', 'admin')
                ->whereNotNull('device_token')
                ->get();

            $boy    = auth()->user();
            $title  = translate('Nouvelle demande de retrait');
            $body   = ($boy->name ?? translate('Un livreur')) . ' ' . translate('a demandé un retrait de') . ' ' . format_price($withdrawRequest->amount);

            foreach ($admins as $admin) {
                \App\Utility\NotificationUtility::sendFirebaseNotification(
                    $admin->device_token,
                    $title,
                    $body,
                    'delivery_boy_withdraw_request',
                    $withdrawRequest->id,
                    $admin->id
                );
            }
        } catch (\Throwable $e) {
            // Silent failure
        }
    }
}
