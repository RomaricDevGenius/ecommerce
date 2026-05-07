@extends('backend.layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 h6">
                    <i class="las la-clock text-warning mr-1"></i>
                    {{ translate('Devis GPS en attente') }}
                </h5>
                <a href="{{ route('gps_shipping.config') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="las la-arrow-left"></i> {{ translate('Retour configuration') }}
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Client') }}</th>
                            <th>{{ translate('Distance') }}</th>
                            <th>{{ translate('Statut') }}</th>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Supplément') }}</th>
                            <th class="text-right">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($quotes as $quote)
                        <tr>
                            <td>
                                <span class="font-weight-bold">{{ $quote->user ? $quote->user->name : '—' }}</span><br>
                                <small class="text-muted">{{ $quote->user ? $quote->user->email : '' }}</small>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    {{ number_format($quote->distance_km, 1) }} km
                                </span>
                            </td>
                            <td>
                                @if($quote->status === 'pending')
                                    <span class="badge badge-warning">{{ translate('En attente') }}</span>
                                @elseif($quote->status === 'confirmed')
                                    <span class="badge badge-success">{{ translate('Devis envoyé') }}</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $quote->created_at->format('d/m/Y H:i') }}</small>
                            </td>
                            <td>
                                @if($quote->supplement_amount)
                                    <span class="text-success font-weight-bold">
                                        {{ number_format($quote->supplement_amount, 0, ',', ' ') }} FCFA
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <button class="btn btn-soft-warning btn-icon btn-sm btn-set-supplement"
                                    data-quote-id="{{ $quote->id }}"
                                    data-client="{{ $quote->user ? $quote->user->name : '?' }}"
                                    data-distance="{{ $quote->distance_km }}"
                                    data-current="{{ $quote->supplement_amount }}"
                                    data-toggle="modal" data-target="#supplementModal"
                                    title="{{ translate('Fixer le devis') }}">
                                    <i class="las la-money-bill-wave"></i>
                                    {{ $quote->supplement_amount ? translate('Modifier') : translate('Fixer') }}
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="las la-check-circle" style="font-size:40px;"></i><br>
                                {{ translate('Aucun devis en attente.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($quotes->hasPages())
            <div class="card-footer">
                {{ $quotes->links() }}
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
                    <h5 class="modal-title h6">{{ translate('Fixer le devis de livraison') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">
                        {{ translate('Client') }} : <strong id="modal-client"></strong>
                    </p>
                    <p class="mb-3">
                        {{ translate('Distance') }} : <strong id="modal-distance"></strong> km
                    </p>
                    <div class="form-group mb-0">
                        <label>{{ translate('Frais de livraison (FCFA)') }}</label>
                        <div class="input-group">
                            <input type="number" name="supplement" id="modal-supplement-input"
                                class="form-control" min="0" step="1" placeholder="Ex: 3500" required>
                            <div class="input-group-append">
                                <span class="input-group-text">FCFA</span>
                            </div>
                        </div>
                        <small class="text-muted">
                            {{ translate('Ce montant sera proposé au client via notification push.') }}
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Annuler') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Envoyer au client') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $('.btn-set-supplement').on('click', function () {
        var quoteId  = $(this).data('quote-id');
        var client   = $(this).data('client');
        var distance = $(this).data('distance');
        var current  = $(this).data('current');

        $('#modal-client').text(client);
        $('#modal-distance').text(distance ? parseFloat(distance).toFixed(1) : '?');
        $('#modal-supplement-input').val(current || '');
        $('#supplementForm').attr('action', '/admin/gps-shipping/supplement/' + quoteId);
    });
</script>
@endsection
