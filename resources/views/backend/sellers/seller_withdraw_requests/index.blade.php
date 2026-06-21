@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('Seller Withdraw Request') }}</h1>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 h6">
            {{ translate('Seller Withdraw Request') }}
        </h5>
        <div class="d-flex align-items-center flex-wrap" style="gap:8px">
            @php $current_filter = request('filter_status', ''); @endphp
            <a href="{{ request()->fullUrlWithQuery(['filter_status' => '', 'page' => 1]) }}"
               class="btn btn-sm {{ $current_filter === '' ? 'btn-primary' : 'btn-soft-primary' }}">
                {{ translate('All') }}
            </a>
            <a href="{{ request()->fullUrlWithQuery(['filter_status' => '0', 'page' => 1]) }}"
               class="btn btn-sm {{ $current_filter === '0' ? 'btn-primary' : 'btn-soft-primary' }}">
                {{ translate('Pending') }}
            </a>
            <a href="{{ request()->fullUrlWithQuery(['filter_status' => '1', 'page' => 1]) }}"
               class="btn btn-sm {{ $current_filter === '1' ? 'btn-primary' : 'btn-soft-primary' }}">
                {{ translate('Paid') }}
            </a>

            <form action="{{ route('withdraw_requests_all') }}" method="GET" class="d-flex" style="gap:4px">
                @if($current_filter !== '')
                    <input type="hidden" name="filter_status" value="{{ $current_filter }}">
                @endif
                <input type="text" name="search" value="{{ request('search') }}"
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
                    <th>{{ translate('Seller') }}</th>
                    <th>{{ translate('Requested Amount') }}</th>
                    <th data-breakpoints="md">{{ translate('Payment Method') }}</th>
                    <th data-breakpoints="md">{{ translate('Account') }}</th>
                    <th data-breakpoints="lg">{{ translate('Date') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th class="text-right">{{ translate('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($seller_withdraw_requests as $key => $seller_withdraw_request)
                    @php
                        $user   = $seller_withdraw_request->user;
                        $rowNum = ($key + 1) + ($seller_withdraw_requests->currentPage() - 1) * $seller_withdraw_requests->perPage();
                    @endphp
                    <tr>
                        <td data-breakpoints="lg">{{ $rowNum }}</td>
                        <td>
                            <strong>{{ $user->name ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $user->phone ?? '' }}</small>
                        </td>
                        <td><strong>{{ single_price($seller_withdraw_request->amount) }}</strong></td>
                        <td data-breakpoints="md">{{ translate($seller_withdraw_request->payment_method) }}</td>
                        <td data-breakpoints="md">{{ $seller_withdraw_request->account_number ?: '—' }}</td>
                        <td data-breakpoints="lg">{{ date('d-m-Y H:i', strtotime($seller_withdraw_request->created_at)) }}</td>
                        <td>
                            @if($seller_withdraw_request->status == 1)
                                <span class="badge badge-inline badge-success">{{ translate('Paid') }}</span>
                            @else
                                <span class="badge badge-inline badge-warning">{{ translate('Pending') }}</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($seller_withdraw_request->status == 0)
                                <a onclick="show_seller_payment_modal('{{ $seller_withdraw_request->user_id }}', '{{ $seller_withdraw_request->id }}');"
                                   class="btn btn-soft-warning btn-icon btn-circle btn-sm"
                                   href="javascript:void(0);"
                                   title="{{ translate('Pay Now') }}">
                                    <i class="las la-money-bill"></i>
                                </a>
                            @endif
                            <a onclick="show_message_modal('{{ $seller_withdraw_request->id }}');"
                               class="btn btn-soft-success btn-icon btn-circle btn-sm"
                               href="javascript:void(0);"
                               title="{{ translate('View') }}">
                                <i class="las la-eye"></i>
                            </a>
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
            {{ $seller_withdraw_requests->links() }}
        </div>
    </div>
</div>

@endsection

@section('modal')
<!-- payment Modal -->
<div class="modal fade" id="payment_modal">
  <div class="modal-dialog">
    <div class="modal-content" id="payment-modal-content">
    </div>
  </div>
</div>

<!-- Message View Modal -->
<div class="modal fade" id="message_modal">
  <div class="modal-dialog">
    <div class="modal-content" id="message-modal-content">
    </div>
  </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    function show_seller_payment_modal(id, seller_withdraw_request_id){
        $.post('{{ route('withdraw_request.payment_modal') }}',{_token:'{{ @csrf_token() }}', id:id, seller_withdraw_request_id:seller_withdraw_request_id}, function(data){
            $('#payment-modal-content').html(data);
            $('#payment_modal').modal('show', {backdrop: 'static'});
            $('.demo-select2-placeholder').select2();
        });
    }

    function show_message_modal(id){
        $.post('{{ route('withdraw_request.message_modal') }}',{_token:'{{ @csrf_token() }}', id:id}, function(data){
            $('#message-modal-content').html(data);
            $('#message_modal').modal('show', {backdrop: 'static'});
        });
    }
</script>
@endsection
