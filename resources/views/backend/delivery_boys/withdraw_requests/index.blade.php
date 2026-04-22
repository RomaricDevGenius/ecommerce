@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('Delivery Boy Withdrawal Requests') }}</h1>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 h6">
            {{ translate('Withdrawal Requests') }}
            @if($pending_count > 0)
                <span class="badge badge-danger ml-2">{{ $pending_count }}</span>
            @endif
        </h5>
        <div class="d-flex align-items-center flex-wrap" style="gap:8px">
            {{-- Filtres statut --}}
            @foreach(['all' => translate('All'), 'pending' => translate('Pending'), 'approved' => translate('Approved'), 'paid' => translate('Paid'), 'rejected' => translate('Rejected')] as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => 1]) }}"
                   class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-soft-primary' }}">
                    {{ $label }}
                </a>
            @endforeach
            {{-- Recherche --}}
            <form action="{{ route('delivery-boy-withdraw-requests.index') }}" method="GET" class="d-flex" style="gap:4px">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="text" name="search" value="{{ $sort_search }}"
                       class="form-control form-control-sm"
                       placeholder="{{ translate('Search by name or phone') }}"
                       style="min-width:180px">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="las la-search"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th data-breakpoints="lg">#</th>
                    <th>{{ translate('Delivery Boy') }}</th>
                    <th>{{ translate('Amount') }}</th>
                    <th data-breakpoints="md">{{ translate('Payment Method') }}</th>
                    <th data-breakpoints="md">{{ translate('Account') }}</th>
                    <th data-breakpoints="lg">{{ translate('Date') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th class="text-right">{{ translate('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($withdraw_requests as $key => $req)
                    @php
                        $boy    = $req->deliveryBoy;
                        $rowNum = ($key + 1) + ($withdraw_requests->currentPage() - 1) * $withdraw_requests->perPage();
                    @endphp
                    <tr>
                        <td data-breakpoints="lg">{{ $rowNum }}</td>
                        <td>
                            <strong>{{ $boy->name ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $boy->phone ?? $boy->email ?? '' }}</small>
                        </td>
                        <td><strong>{{ single_price($req->amount) }}</strong></td>
                        <td data-breakpoints="md">{{ $req->payment_method_label }}</td>
                        <td data-breakpoints="md">{{ $req->account_number ?: '—' }}</td>
                        <td data-breakpoints="lg">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="badge badge-inline badge-{{ $req->status_badge }}">
                                {{ $req->status_label }}
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="javascript:void(0)"
                               onclick="showDetail({{ $req->id }})"
                               class="btn btn-soft-info btn-icon btn-circle btn-sm"
                               title="{{ translate('View Details') }}">
                                <i class="las la-eye"></i>
                            </a>

                            @if($req->status === 'pending')
                                <a href="javascript:void(0)"
                                   onclick="confirmApprove({{ $req->id }}, '{{ addslashes($boy->name ?? '') }}', '{{ single_price($req->amount) }}')"
                                   class="btn btn-soft-success btn-icon btn-circle btn-sm"
                                   title="{{ translate('Approve') }}">
                                    <i class="las la-check"></i>
                                </a>
                                <a href="javascript:void(0)"
                                   onclick="showRejectModal({{ $req->id }})"
                                   class="btn btn-soft-danger btn-icon btn-circle btn-sm"
                                   title="{{ translate('Reject') }}">
                                    <i class="las la-times"></i>
                                </a>
                            @endif

                            @if($req->status === 'approved')
                                <a href="javascript:void(0)"
                                   onclick="showConfirmPaymentModal({{ $req->id }}, '{{ addslashes($boy->name ?? '') }}', '{{ single_price($req->amount) }}')"
                                   class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                   title="{{ translate('Confirm Payment') }}">
                                    <i class="las la-money-bill-wave"></i>
                                </a>
                                <a href="javascript:void(0)"
                                   onclick="showRejectModal({{ $req->id }})"
                                   class="btn btn-soft-danger btn-icon btn-circle btn-sm"
                                   title="{{ translate('Reject') }}">
                                    <i class="las la-times"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            {{ translate('No withdrawal requests found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $withdraw_requests->links() }}
        </div>
    </div>
</div>

@endsection


@section('modal')

{{-- Détail --}}
<div class="modal fade" id="detail-modal">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content" id="detail-modal-content"></div>
    </div>
</div>

{{-- Approuver --}}
<div class="modal fade" id="approve-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-2rem">
            <div class="modal-body text-center">
                <i class="las la-check-circle text-success" style="font-size:56px"></i>
                <p class="mt-3 mb-4 fs-16 fw-700" id="approve-body"></p>
                <form id="approve-form" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="button" class="btn btn-light rounded-2 mt-2 w-150px" data-dismiss="modal">
                        {{ translate('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-success rounded-2 mt-2 w-200px">
                        <i class="las la-check mr-1"></i>{{ translate('Approve') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Confirmer paiement --}}
<div class="modal fade" id="payment-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h6">{{ translate('Confirm Payment') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="payment-form" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p class="mb-3" id="payment-body"></p>
                    <div class="form-group mb-0">
                        <label class="fw-600">{{ translate('Admin Note') }} <small class="text-muted">({{ translate('optional') }})</small></label>
                        <textarea name="admin_note" class="form-control" rows="3"
                                  placeholder="{{ translate('e.g. Transferred via Orange Money #TXN123') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="las la-check mr-1"></i>{{ translate('Mark as Paid') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Rejeter --}}
<div class="modal fade" id="reject-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h6">{{ translate('Reject Withdrawal Request') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="reject-form" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label class="fw-600">{{ translate('Reason for Rejection') }} <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required
                                  placeholder="{{ translate('Explain why this request is rejected...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="las la-times mr-1"></i>{{ translate('Reject') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection


@section('script')
<script>
function showDetail(id) {
    $.post('{{ route('delivery-boy-withdraw-requests.detail') }}', {
        _token: '{{ csrf_token() }}', id: id
    }, function(data) {
        $('#detail-modal-content').html(data);
        $('#detail-modal').modal('show');
    });
}

function confirmApprove(id, name, amount) {
    $('#approve-body').text('{{ translate('Approve withdrawal of') }} ' + amount + ' {{ translate('for') }} ' + name + ' ?');
    $('#approve-form').attr('action', '/admin/delivery-boy-withdraw-requests/' + id + '/approve');
    $('#approve-modal').modal('show');
}

function showConfirmPaymentModal(id, name, amount) {
    $('#payment-body').text('{{ translate('Confirm that you have paid') }} ' + amount + ' {{ translate('to') }} ' + name + '.');
    $('#payment-form').attr('action', '/admin/delivery-boy-withdraw-requests/' + id + '/confirm-payment');
    $('#payment-form textarea[name="admin_note"]').val('');
    $('#payment-modal').modal('show');
}

function showRejectModal(id) {
    $('#reject-form').attr('action', '/admin/delivery-boy-withdraw-requests/' + id + '/reject');
    $('#reject-form textarea[name="rejection_reason"]').val('');
    $('#reject-modal').modal('show');
}
</script>
@endsection
