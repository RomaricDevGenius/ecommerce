<?php

namespace App\Http\Controllers;

use App\Models\DeliveryBoy;
use App\Models\DeliveryBoyPayment;
use App\Models\DeliveryBoyWithdrawRequest;
use App\Models\User;
use App\Utility\NotificationUtility;
use Illuminate\Http\Request;

class DeliveryBoyWithdrawRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_delivery_boy_withdraw_requests'])->only('index');
    }

    public function index(Request $request)
    {
        $status     = $request->get('status', 'all');
        $sort_search = $request->get('search');

        $query = DeliveryBoyWithdrawRequest::with('deliveryBoy')->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($sort_search) {
            $userIds = User::where('user_type', 'delivery_boy')
                ->where(function ($q) use ($sort_search) {
                    $q->where('name', 'like', "%{$sort_search}%")
                      ->orWhere('email', 'like', "%{$sort_search}%")
                      ->orWhere('phone', 'like', "%{$sort_search}%");
                })->pluck('id');
            $query->whereIn('delivery_boy_id', $userIds);
        }

        // Mark unviewed as viewed
        $query->clone()->where('viewed', false)->update(['viewed' => true]);

        $withdraw_requests = $query->paginate(15)->withQueryString();
        $pending_count     = DeliveryBoyWithdrawRequest::where('status', 'pending')->count();

        return view('backend.delivery_boys.withdraw_requests.index', compact(
            'withdraw_requests',
            'status',
            'sort_search',
            'pending_count'
        ));
    }

    public function approve(Request $request, $id)
    {
        $withdraw_request = DeliveryBoyWithdrawRequest::findOrFail($id);

        if ($withdraw_request->status !== 'pending') {
            flash(translate('This request is no longer pending.'))->error();
            return back();
        }

        $delivery_boy = DeliveryBoy::where('user_id', $withdraw_request->delivery_boy_id)->first();

        if (!$delivery_boy || $withdraw_request->amount > $delivery_boy->total_earning) {
            flash(translate('Insufficient balance for this withdrawal.'))->error();
            return back();
        }

        $withdraw_request->status = 'approved';
        $withdraw_request->save();

        $this->notifyDeliveryBoy(
            $withdraw_request->deliveryBoy,
            translate('Retrait approuvé'),
            translate('Votre demande de retrait de') . ' ' . single_price($withdraw_request->amount) . ' ' . translate('a été approuvée.'),
            $withdraw_request->id
        );

        // SMS au livreur
        $this->sendWithdrawSms('approved', $withdraw_request);

        flash(translate('Withdrawal request approved.'))->success();
        return back();
    }

    public function confirmPayment(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        $withdraw_request = DeliveryBoyWithdrawRequest::findOrFail($id);

        if ($withdraw_request->status !== 'approved') {
            flash(translate('Only approved requests can be confirmed as paid.'))->error();
            return back();
        }

        $delivery_boy = DeliveryBoy::where('user_id', $withdraw_request->delivery_boy_id)->first();

        if (!$delivery_boy) {
            flash(translate('Delivery boy not found.'))->error();
            return back();
        }

        if ($withdraw_request->amount > $delivery_boy->total_earning) {
            flash(translate('Insufficient balance. The earning may have changed.'))->error();
            return back();
        }

        // Deduct from total_earning and create payment record
        $delivery_boy->total_earning -= $withdraw_request->amount;
        $delivery_boy->save();

        $payment            = new DeliveryBoyPayment;
        $payment->user_id   = $withdraw_request->delivery_boy_id;
        $payment->payment   = $withdraw_request->amount;
        $payment->save();

        $withdraw_request->status     = 'paid';
        $withdraw_request->admin_note = $request->admin_note;
        $withdraw_request->save();

        $this->notifyDeliveryBoy(
            $withdraw_request->deliveryBoy,
            translate('Paiement effectué'),
            translate('Votre retrait de') . ' ' . single_price($withdraw_request->amount) . ' ' . translate('a été payé.'),
            $withdraw_request->id
        );

        // SMS au livreur
        $this->sendWithdrawSms('paid', $withdraw_request);

        flash(translate('Payment confirmed successfully.'))->success();
        return back();
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $withdraw_request = DeliveryBoyWithdrawRequest::findOrFail($id);

        if (!in_array($withdraw_request->status, ['pending', 'approved'])) {
            flash(translate('This request cannot be rejected.'))->error();
            return back();
        }

        $withdraw_request->status           = 'rejected';
        $withdraw_request->rejection_reason = $request->rejection_reason;
        $withdraw_request->save();

        $this->notifyDeliveryBoy(
            $withdraw_request->deliveryBoy,
            translate('Retrait refusé'),
            translate('Votre demande de retrait de') . ' ' . single_price($withdraw_request->amount) . ' ' . translate('a été refusée.'),
            $withdraw_request->id
        );

        // SMS au livreur
        $this->sendWithdrawSms('rejected', $withdraw_request);

        flash(translate('Withdrawal request rejected.'))->success();
        return back();
    }

    public function detail(Request $request)
    {
        $req = DeliveryBoyWithdrawRequest::with('deliveryBoy')->findOrFail($request->id);
        return view('backend.delivery_boys.withdraw_requests.detail_modal', compact('req'));
    }

    private function sendWithdrawSms(string $event, $withdrawRequest): void
    {
        if (!addon_is_activated('otp_system')) {
            return;
        }

        $phone = $withdrawRequest->deliveryBoy?->phone;
        if (!$phone) {
            return;
        }

        try {
            match ($event) {
                'approved' => \App\Utility\SmsUtility::delivery_boy_withdraw_approved($phone, $withdrawRequest),
                'paid'     => \App\Utility\SmsUtility::delivery_boy_withdraw_paid($phone, $withdrawRequest),
                'rejected' => \App\Utility\SmsUtility::delivery_boy_withdraw_rejected($phone, $withdrawRequest),
                default    => null,
            };
        } catch (\Throwable $e) {
            // Ne jamais bloquer l'action admin à cause d'un SMS
        }
    }

    private function notifyDeliveryBoy(?User $user, string $title, string $body, int $requestId): void
    {
        if (!$user || !get_setting('google_firebase') || !$user->device_token) {
            return;
        }

        try {
            NotificationUtility::sendFirebaseNotification(
                $user->device_token,
                $title,
                $body,
                'delivery_boy_withdraw_request',
                $requestId,
                $user->id
            );
        } catch (\Throwable $e) {
            // Notification failure should never block the admin action
        }
    }
}
