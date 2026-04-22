@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h3 class="h3">{{ translate('Delivery Boy Withdrawal Requests') }}</h3>
        </div>
        @if($pending_count > 0)
            <div class="col-auto">
                <span class="badge badge-danger badge-pill fs-14 px-3 py-2">
                    {{ $pending_count }} {{ translate('pending') }}
                </span>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header d-block d-lg-flex align-items-center flex-wrap gap-3">
        <h5 class="mb-2 mb-lg-0 h6">{{ translate('Withdrawal Requests') }}</h5>
        <div class="ml-auto d-flex flex-wrap gap-2 align-items-center">
            {{-- Status filter tabs --}}
            <div class="btn-group" role="group">
                @foreach(['all' => translate('All'), 'pending' => translate('Pending'), 'approved' => translate('Approved'), 'paid' => translate('Paid'), 'rejected' => translate('Rejected')] as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => 1]) }}"
                       class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-outline-primary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            {{-- Search --}}
            <form action="{{ route('delivery-boy-withdraw-requests.index') }}" method="GET" class="d-flex">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="text" name="search" value="{{ $sort_search }}" class="form-control form-control-sm"
                       placeholder="{{ translate('Search by name or phone') }}" style="min-width:200px">
                <button type="submit" class="btn btn-sm btn-primary ml-1">
                    <i class="las la-search"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table aiz-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="text-center" width="40">#</th>
                        <th>{{ translate('Delivery Boy') }}</th>
                        <th>{{ translate('Amount') }}</th>
                        <th data-breakpoints="md">{{ translate('Payment Method') }}</th>
                        <th data-breakpoints="md">{{ translate('Account') }}</th>
                        <th data-breakpoints="lg">{{ translate('Date') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right" width="180">{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdraw_requests as $key => $req)
                        @php
                            $boy       = $req->deliveryBoy;
                            $row_num   = ($key + 1) + ($withdraw_requests->currentPage() - 1) * $withdraw_requests->perPage();
                        @endphp
                        <tr>
                            <td class="text-center">{{ $row_num }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm mr-2 rounded-circle overflow-hidden">
                                        <img src="{{ $boy && $boy->avatar ? uploaded_asset($boy->avatar) : asset('public/assets/img/avatar-place.png') }}"
                                             alt="{{ $boy->name ?? '—' }}" class="img-fit">
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-600">{{ $boy->name ?? '—' }}</p>
                                        <small class="text-muted">{{ $boy->phone ?? $boy->email ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="fw-700 text-dark">{{ single_price($req->amount) }}</td>
                            <td>{{ $req->payment_method_label }}</td>
                            <td>
                                <span class="badge badge-light border">{{ $req->account_number ?: '—' }}</span>
                            </td>
                            <td>
                                <span title="{{ $req->created_at }}">
                                    {{ $req->created_at->format('d/m/Y H:i') }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $req->status_badge }} badge-inline px-2 py-1">
                                    {{ $req->status_label }}
                                </span>
                            </td>
                            <td class="text-right">
                                <button type="button" class="btn btn-soft-info btn-icon btn-circle btn-sm"
                                        onclick="showDetail({{ $req->id }})"
                                        title="{{ translate('View Details') }}">
                                    <i class="las la-eye"></i>
                                </button>

                                @if($req->status === 'pending')
                                    <button type="button"
                                            class="btn btn-soft-success btn-icon btn-circle btn-sm"
                                            onclick="confirmApprove({{ $req->id }}, '{{ $boy->name ?? '' }}', '{{ single_price($req->amount) }}')"
                                            title="{{ translate('Approve') }}">
                                        <i class="las la-check"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-soft-danger btn-icon btn-circle btn-sm"
                                            onclick="showRejectModal({{ $req->id }})"
                                            title="{{ translate('Reject') }}">
                                        <i class="las la-times"></i>
                                    </button>
                                @endif

                                @if($req->status === 'approved')
                                    <button type="button"
                                            class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                            onclick="showConfirmPaymentModal({{ $req->id }}, '{{ $boy->name ?? '' }}', '{{ single_price($req->amount) }}')"
                                            title="{{ translate('Confirm Payment') }}">
                                        <i class="las la-money-bill-wave"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-soft-danger btn-icon btn-circle btn-sm"
                                            onclick="showRejectModal({{ $req->id }})"
                                            title="{{ translate('Reject') }}">
                                        <i class="las la-times"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="las la-inbox fs-40 d-block mb-2"></i>
                                    {{ translate('No withdrawal requests found.') }}
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($withdraw_requests->hasPages())
        <div class="card-footer">
            <div class="aiz-pagination">
                {{ $withdraw_requests->links() }}
            </div>
        </div>
    @endif
</div>

@endsection


@section('modal')

{{-- Detail Modal --}}
<div class="modal fade" id="detail-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content" id="detail-modal-content"></div>
    </div>
</div>

{{-- Approve Confirmation Modal --}}
<div class="modal fade" id="approve-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content p-2rem">
            <div class="modal-body text-center">
                <i class="las la-check-circle text-success" style="font-size:64px"></i>
                <h5 class="mt-3 mb-1 fw-700" id="approve-title">{{ translate('Approve Withdrawal?') }}</h5>
                <p class="text-muted mb-4" id="approve-body"></p>
                <form id="approve-form" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light rounded-2 w-140px" data-dismiss="modal">
                            {{ translate('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn-success rounded-2 w-200px">
                            <i class="las la-check mr-1"></i> {{ translate('Approve') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Confirm Payment Modal --}}
<div class="modal fade" id="payment-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-700">
                    <i class="las la-money-bill-wave text-primary mr-2"></i>
                    {{ translate('Confirm Payment') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="payment-form" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-center mb-3" id="payment-info">
                        <i class="las la-info-circle fs-20 mr-2"></i>
                        <span id="payment-body"></span>
                    </div>
                    <div class="form-group mb-0">
                        <label class="fw-600">{{ translate('Admin Note') }} <span class="text-muted fw-400 fs-12">({{ translate('optional') }})</span></label>
                        <textarea name="admin_note" class="form-control" rows="3"
                                  placeholder="{{ translate('e.g. Transferred via Orange Money #TXN123') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="las la-check mr-1"></i> {{ translate('Mark as Paid') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="reject-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-700">
                    <i class="las la-times-circle text-danger mr-2"></i>
                    {{ translate('Reject Withdrawal Request') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
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
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="las la-times mr-1"></i> {{ translate('Reject') }}
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
        _token: '{{ csrf_token() }}',
        id: id
    }, function(data) {
        $('#detail-modal-content').html(data);
        $('#detail-modal').modal('show');
    });
}

function confirmApprove(id, name, amount) {
    $('#approve-body').text(`{{ translate('Approve withdrawal of') }} ${amount} {{ translate('for') }} ${name} ?`);
    $('#approve-form').attr('action', '/admin/delivery-boy-withdraw-requests/' + id + '/approve');
    $('#approve-modal').modal('show');
}

function showConfirmPaymentModal(id, name, amount) {
    $('#payment-body').text(`{{ translate('Confirm that you have paid') }} ${amount} {{ translate('to') }} ${name}.`);
    $('#payment-form').attr('action', '/admin/delivery-boy-withdraw-requests/' + id + '/confirm-payment');
    // Reset note field
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
