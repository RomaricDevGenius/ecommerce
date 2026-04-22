<div class="modal-header border-0 pb-0">
    <h5 class="modal-title fw-700">
        <i class="las la-file-invoice text-primary mr-2"></i>
        {{ translate('Withdrawal Request Details') }}
    </h5>
    <button type="button" class="close" data-dismiss="modal">
        <span>&times;</span>
    </button>
</div>
<div class="modal-body pt-2">
    @php $boy = $req->deliveryBoy; @endphp

    {{-- Delivery boy info --}}
    <div class="d-flex align-items-center mb-3 p-3 bg-soft-secondary rounded">
        <div class="avatar avatar-md mr-3 rounded-circle overflow-hidden flex-shrink-0">
            <img src="{{ $boy && $boy->avatar ? uploaded_asset($boy->avatar) : asset('public/assets/img/avatar-place.png') }}"
                 alt="{{ $boy->name ?? '' }}" class="img-fit">
        </div>
        <div>
            <p class="mb-0 fw-700 fs-15">{{ $boy->name ?? '—' }}</p>
            <small class="text-muted">{{ $boy->phone ?? $boy->email ?? '' }}</small>
        </div>
        <div class="ml-auto text-right">
            <span class="badge badge-{{ $req->status_badge }} badge-inline px-3 py-2 fs-13">
                {{ $req->status_label }}
            </span>
        </div>
    </div>

    {{-- Financial info --}}
    <table class="table table-sm table-borderless mb-3">
        <tbody>
            <tr>
                <td class="text-muted fw-600" width="45%">{{ translate('Requested Amount') }}</td>
                <td class="fw-700 fs-16 text-dark">{{ single_price($req->amount) }}</td>
            </tr>
            <tr>
                <td class="text-muted fw-600">{{ translate('Payment Method') }}</td>
                <td>{{ $req->payment_method_label ?: '—' }}</td>
            </tr>
            <tr>
                <td class="text-muted fw-600">{{ translate('Account Number') }}</td>
                <td>
                    @if($req->account_number)
                        <code class="bg-light px-2 py-1 rounded">{{ $req->account_number }}</code>
                    @else
                        —
                    @endif
                </td>
            </tr>
            <tr>
                <td class="text-muted fw-600">{{ translate('Request Date') }}</td>
                <td>{{ $req->created_at->format('d/m/Y à H:i') }}</td>
            </tr>
            @if($req->notes)
            <tr>
                <td class="text-muted fw-600">{{ translate('Message') }}</td>
                <td>{{ $req->notes }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    {{-- Current balance --}}
    @php
        $deliveryBoyRecord = \App\Models\DeliveryBoy::where('user_id', $req->delivery_boy_id)->first();
    @endphp
    @if($deliveryBoyRecord)
        <div class="alert alert-light border d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted fw-600">{{ translate('Current Earning Balance') }}</span>
            <span class="fw-700 fs-15 {{ $deliveryBoyRecord->total_earning >= $req->amount ? 'text-success' : 'text-danger' }}">
                {{ single_price($deliveryBoyRecord->total_earning) }}
                @if($deliveryBoyRecord->total_earning < $req->amount)
                    <small class="d-block text-danger fs-11">{{ translate('Insufficient balance') }}</small>
                @endif
            </span>
        </div>
    @endif

    {{-- Status-specific info --}}
    @if($req->status === 'rejected' && $req->rejection_reason)
        <div class="alert alert-danger mb-0">
            <p class="fw-600 mb-1">{{ translate('Rejection Reason') }}</p>
            <p class="mb-0">{{ $req->rejection_reason }}</p>
        </div>
    @endif

    @if($req->status === 'paid' && $req->admin_note)
        <div class="alert alert-success mb-0">
            <p class="fw-600 mb-1">{{ translate('Payment Note') }}</p>
            <p class="mb-0">{{ $req->admin_note }}</p>
        </div>
    @endif
</div>
<div class="modal-footer border-0">
    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>

    @if($req->status === 'pending')
        <button type="button" class="btn btn-danger"
                data-dismiss="modal"
                onclick="showRejectModal({{ $req->id }})">
            <i class="las la-times mr-1"></i>{{ translate('Reject') }}
        </button>
        <button type="button" class="btn btn-success"
                data-dismiss="modal"
                onclick="confirmApprove({{ $req->id }}, '{{ $req->deliveryBoy->name ?? '' }}', '{{ single_price($req->amount) }}')">
            <i class="las la-check mr-1"></i>{{ translate('Approve') }}
        </button>
    @endif

    @if($req->status === 'approved')
        <button type="button" class="btn btn-danger"
                data-dismiss="modal"
                onclick="showRejectModal({{ $req->id }})">
            <i class="las la-times mr-1"></i>{{ translate('Reject') }}
        </button>
        <button type="button" class="btn btn-primary"
                data-dismiss="modal"
                onclick="showConfirmPaymentModal({{ $req->id }}, '{{ $req->deliveryBoy->name ?? '' }}', '{{ single_price($req->amount) }}')">
            <i class="las la-money-bill-wave mr-1"></i>{{ translate('Confirm Payment') }}
        </button>
    @endif
</div>
