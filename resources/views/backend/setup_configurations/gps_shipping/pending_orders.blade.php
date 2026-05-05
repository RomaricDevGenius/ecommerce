@extends('backend.layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 h6">
                    <i class="las la-clock text-warning mr-1"></i>
                    {{ translate('Commandes GPS en attente de supplément') }}
                </h5>
                <a href="{{ route('gps_shipping.config') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="las la-arrow-left"></i> {{ translate('Retour configuration') }}
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Commande') }}</th>
                            <th>{{ translate('Client') }}</th>
                            <th>{{ translate('Distance') }}</th>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Supplément') }}</th>
                            <th class="text-right">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('orders.show', $order->id) }}" class="font-weight-bold">
                                    {{ $order->code }}
                                </a>
                            </td>
                            <td>
                                {{ $order->user ? $order->user->name : '—' }}<br>
                                <small class="text-muted">{{ $order->user ? $order->user->email : '' }}</small>
                            </td>
                            <td>
                                @if($order->gps_distance_km)
                                    <span class="badge badge-info">
                                        {{ number_format($order->gps_distance_km, 1) }} km
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $order->created_at->format('d/m/Y H:i') }}</small>
                            </td>
                            <td>
                                @if($order->gps_supplement)
                                    <span class="text-success font-weight-bold">
                                        {{ number_format($order->gps_supplement, 0, ',', ' ') }} FCFA
                                    </span>
                                @else
                                    <span class="text-warning">En attente</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <button class="btn btn-sm btn-warning btn-set-supplement"
                                    data-order-id="{{ $order->id }}"
                                    data-order-code="{{ $order->code }}"
                                    data-distance="{{ $order->gps_distance_km }}"
                                    data-toggle="modal" data-target="#supplementModal">
                                    <i class="las la-money-bill-wave"></i>
                                    {{ translate('Fixer supplément') }}
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="las la-check-circle" style="font-size:40px;"></i><br>
                                {{ translate('Aucune commande en attente de supplément.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())
            <div class="card-footer">
                {{ $orders->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal supplément --}}
<div class="modal fade" id="supplementModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form id="supplementForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Fixer le supplément') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">
                        {{ translate('Commande') }} : <strong id="modal-order-code"></strong>
                    </p>
                    <p class="mb-3">
                        {{ translate('Distance') }} : <strong id="modal-distance"></strong> km
                    </p>
                    <div class="form-group">
                        <label>{{ translate('Supplément de livraison (FCFA)') }}</label>
                        <div class="input-group">
                            <input type="number" name="supplement" class="form-control"
                                min="0" step="1" placeholder="Ex: 3500" required>
                            <div class="input-group-append">
                                <span class="input-group-text">FCFA</span>
                            </div>
                        </div>
                        <small class="text-muted">
                            {{ translate('Ce montant sera affiché au client pour paiement.') }}
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Annuler') }}</button>
                    <button type="submit" class="btn btn-warning">{{ translate('Confirmer') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $('.btn-set-supplement').on('click', function () {
        var orderId   = $(this).data('order-id');
        var orderCode = $(this).data('order-code');
        var distance  = $(this).data('distance');

        $('#modal-order-code').text(orderCode);
        $('#modal-distance').text(distance ? parseFloat(distance).toFixed(1) : '?');
        $('#supplementForm').attr('action', '/admin/gps-shipping/supplement/' + orderId);
    });
</script>
@endsection
