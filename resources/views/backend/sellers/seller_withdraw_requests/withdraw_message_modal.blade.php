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
    @php
        $seller = $seller_withdraw_request->user;
        $shop   = optional($seller)->shop;
    @endphp

    <div class="d-flex align-items-center mb-3 p-3 bg-soft-secondary rounded">
        <div class="avatar avatar-md mr-3 rounded-circle overflow-hidden flex-shrink-0">
            <img src="{{ $seller && $seller->avatar_original ? uploaded_asset($seller->avatar_original) : asset('public/assets/img/avatar-place.png') }}"
                 alt="{{ $seller->name ?? '' }}" class="img-fit">
        </div>
        <div>
            <p class="mb-0 fw-700 fs-15">{{ $seller->name ?? '—' }}</p>
            <small class="text-muted">{{ $seller->phone ?? $seller->email ?? '' }}</small>
        </div>
        <div class="ml-auto text-right">
            @if($seller_withdraw_request->status == 0)
                <span class="badge badge-warning badge-inline px-3 py-2 fs-13">
                    {{ translate('Pending') }}
                </span>
            @else
                <span class="badge badge-success badge-inline px-3 py-2 fs-13">
                    {{ translate('Paid') }}
                </span>
            @endif
        </div>
    </div>

    <table class="table table-sm table-borderless mb-3">
        <tbody>
            <tr>
                <td class="text-muted fw-600" width="45%">{{ translate('Requested Amount') }}</td>
                <td class="fw-700 fs-16 text-dark">{{ single_price($seller_withdraw_request->amount) }}</td>
            </tr>
            <tr>
                <td class="text-muted fw-600">{{ translate('Payment Method') }}</td>
                <td>{{ translate($seller_withdraw_request->payment_method) ?: '—' }}</td>
            </tr>
            <tr>
                <td class="text-muted fw-600">{{ translate('Account Number') }}</td>
                <td>
                    @if($seller_withdraw_request->account_number)
                        <code class="bg-light px-2 py-1 rounded">{{ $seller_withdraw_request->account_number }}</code>
                    @else
                        —
                    @endif
                </td>
            </tr>
            <tr>
                <td class="text-muted fw-600">{{ translate('Request Date') }}</td>
                <td>{{ date('d-m-Y à H:i', strtotime($seller_withdraw_request->created_at)) }}</td>
            </tr>
            @if($seller_withdraw_request->message)
            <tr>
                <td class="text-muted fw-600">{{ translate('Message') }}</td>
                <td>{{ $seller_withdraw_request->message }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    @php $solde = optional($shop)->admin_to_pay ?? 0; @endphp
    <div class="alert alert-light border d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted fw-600">{{ translate('Current Seller Balance') }}</span>
        <span class="fw-700 fs-15 {{ $solde > 0 ? 'text-success' : 'text-danger' }}">
            {{ single_price($solde) }}
            @if($solde == 0 && $seller_withdraw_request->status == 0)
                <small class="d-block text-danger fs-11">{{ translate('Insufficient balance') }}</small>
            @endif
        </span>
    </div>
</div>
<div class="modal-footer border-0">
    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>
</div>
