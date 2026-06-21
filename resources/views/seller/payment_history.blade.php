@extends('seller.layouts.app')

@section('panel_content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Payment History') }}</h5>
        </div>
        @if (count($payments) > 0)
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Date')}}</th>
                            <th>{{ translate('Amount')}}</th>
                            <th>{{ translate('Payment Method')}}</th>
                            <th data-breakpoints="lg">{{ translate('TRX ID')}}</th>
                            <th data-breakpoints="lg">{{ translate('Details')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $key => $payment)
                            <tr>
                                <td>{{ ($key+1) + ($payments->currentPage() - 1) * $payments->perPage() }}</td>
                                <td>{{ date('d-m-Y', strtotime($payment->created_at)) }}</td>
                                <td class="fw-700">{{ single_price($payment->amount) }}</td>
                                <td>{{ translate(ucfirst(str_replace('_', ' ', $payment->payment_method))) }}</td>
                                <td>{{ $payment->txn_code ?? '—' }}</td>
                                <td>{{ $payment->payment_details ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ $payments->links() }}
                </div>
            </div>
        @else
            <div class="card-body">
                <p class="text-muted text-center py-4">{{ translate('No payment history found') }}</p>
            </div>
        @endif
    </div>

@endsection
