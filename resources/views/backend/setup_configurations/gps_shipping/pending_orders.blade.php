@extends('backend.layouts.app')

@section('styles')
<style>
    .gps-badge-dist {
        display: inline-flex; align-items: center; gap: 4px;
        background: #eef2ff; color: #4f46e5;
        border: 1px solid #c7d2fe; border-radius: 20px;
        padding: 3px 10px; font-size: 12px; font-weight: 600; white-space: nowrap;
    }
    .gps-badge-pending {
        display: inline-flex; align-items: center; gap: 6px;
        background: #fff7ed; color: #c2410c;
        border: 1px solid #fed7aa; border-radius: 20px;
        padding: 3px 10px; font-size: 12px; font-weight: 600;
    }
    .gps-badge-pending::before {
        content: ''; display: inline-block;
        width: 7px; height: 7px; border-radius: 50%; background: #fb923c;
    }
    .gps-badge-confirmed {
        display: inline-flex; align-items: center; gap: 6px;
        background: #f0fdf4; color: #15803d;
        border: 1px solid #bbf7d0; border-radius: 20px;
        padding: 3px 10px; font-size: 12px; font-weight: 600;
    }
    .gps-badge-confirmed::before {
        content: ''; display: inline-block;
        width: 7px; height: 7px; border-radius: 50%; background: #22c55e;
    }

    /* Infos trajet dans le modal */
    .route-info-box {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .route-info-row {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 12px 16px;
    }
    .route-info-row + .route-info-row {
        border-top: 1px solid #e9ecef;
    }
    .route-dot {
        width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; margin-top: 3px;
    }
    .route-dot.store  { background: #22c55e; }
    .route-dot.client { background: #ef4444; }
    .route-info-label { font-size: 11px; color: #6b7280; margin-bottom: 2px; }
    .route-info-value { font-size: 13px; font-weight: 600; color: #111827; }
    .route-info-sub   { font-size: 11px; color: #9ca3af; margin-top: 1px; font-family: monospace; }
    .route-dist-row {
        display: flex; align-items: center; justify-content: center;
        padding: 8px 16px; border-top: 1px solid #e9ecef;
        gap: 8px; background: #fff;
    }
    .route-dist-badge {
        background: #eff6ff; color: #1d4ed8;
        border: 1px solid #bfdbfe; border-radius: 20px;
        padding: 3px 14px; font-size: 12px; font-weight: 700;
        display: flex; align-items: center; gap: 5px;
    }
</style>
@endsection

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
                    <i class="las la-arrow-left mr-1"></i>{{ translate('Retour configuration') }}
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
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
                                    <span class="font-weight-600">{{ $quote->user ? $quote->user->name : '—' }}</span><br>
                                    <small class="text-muted">{{ $quote->user ? $quote->user->email : '' }}</small>
                                </td>
                                <td>
                                    <span class="gps-badge-dist">
                                        <i class="las la-route" style="font-size:13px"></i>
                                        {{ number_format($quote->distance_km, 1) }} km
                                    </span>
                                </td>
                                <td>
                                    @if($quote->status === 'pending')
                                        <span class="gps-badge-pending">{{ translate('En attente') }}</span>
                                    @elseif($quote->status === 'confirmed')
                                        <span class="gps-badge-confirmed">{{ translate('Devis envoyé') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span>{{ $quote->created_at->format('d/m/Y') }}</span><br>
                                    <small class="text-muted">{{ $quote->created_at->format('H:i') }}</small>
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
                                    <button type="button"
                                        class="btn btn-soft-primary btn-sm btn-fix-quote"
                                        data-quote-id="{{ $quote->id }}"
                                        data-client="{{ $quote->user ? $quote->user->name : '?' }}"
                                        data-email="{{ $quote->user ? $quote->user->email : '' }}"
                                        data-distance="{{ number_format($quote->distance_km, 1) }}"
                                        data-lat="{{ $quote->delivery_lat }}"
                                        data-lng="{{ $quote->delivery_lng }}"
                                        data-current="{{ $quote->supplement_amount }}"
                                        data-toggle="modal" data-target="#supplementModal">
                                        <i class="las la-money-bill-wave mr-1"></i>{{ $quote->supplement_amount ? translate('Modifier') : translate('Fixer le devis') }}
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="las la-check-circle" style="font-size:48px;color:#ccc"></i>
                                    <p class="mt-2 mb-0">{{ translate('Aucun devis en attente.') }}</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($quotes->hasPages())
            <div class="card-footer">{{ $quotes->links() }}</div>
            @endif
        </div>
    </div>
</div>

{{-- Modal formulaire --}}
<div class="modal fade" id="supplementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="mb-0 h6">
                    <i class="las la-money-bill-wave mr-1"></i>{{ translate('Fixer le devis de livraison') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal"></button>
            </div>

            <form id="supplementForm" method="POST" action="">
                @csrf
                <div class="modal-body">

                    {{-- Infos trajet --}}
                    <div class="route-info-box">
                        <div class="route-info-row">
                            <div class="route-dot store mt-1"></div>
                            <div>
                                <div class="route-info-label">{{ translate('Départ') }}</div>
                                <div class="route-info-value">{{ get_setting('delivery_pickup_address', translate('Boutique')) }}</div>
                                <div class="route-info-sub">
                                    {{ (float) get_setting('delivery_pickup_latitude','—') }},
                                    {{ (float) get_setting('delivery_pickup_longitude','—') }}
                                </div>
                            </div>
                        </div>
                        <div class="route-dist-row">
                            <div class="route-dist-badge">
                                <i class="las la-road"></i>
                                <span id="modal-dist-label">—</span>
                            </div>
                        </div>
                        <div class="route-info-row">
                            <div class="route-dot client mt-1"></div>
                            <div>
                                <div class="route-info-label">{{ translate('Arrivée — Client') }}</div>
                                <div class="route-info-value" id="modal-client-name">—</div>
                                <div class="route-info-sub" id="modal-client-coords">—</div>
                            </div>
                        </div>
                    </div>

                    {{-- Champ montant --}}
                    <div class="form-group row">
                        <label class="col-md-4 col-from-label">
                            {{ translate('Frais de livraison') }}
                        </label>
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="number" name="supplement" id="modal-supplement-input"
                                    class="form-control" min="0" step="1"
                                    placeholder="{{ translate('Montant en FCFA') }}" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-muted" style="font-size:12px;margin-top:-8px">
                        <i class="las la-paper-plane mr-1"></i>{{ translate('Le client sera notifié par push et email.') }}
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">
                        {{ translate('Annuler') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="las la-paper-plane mr-1"></i>{{ translate('Envoyer au client') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    var SUPP_URL = "{{ route('gps_shipping.supplement', ':id') }}";

    $(document).on('click', '.btn-fix-quote', function () {
        var $b   = $(this);
        var lat  = parseFloat($b.data('lat'));
        var lng  = parseFloat($b.data('lng'));

        $('#modal-client-name').text($b.data('client') || '—');
        $('#modal-dist-label').text(($b.data('distance') || '?') + ' km');
        $('#modal-client-coords').text(
            (!isNaN(lat) && !isNaN(lng))
                ? lat.toFixed(5) + ', ' + lng.toFixed(5)
                : '—'
        );
        $('#modal-supplement-input').val($b.data('current') || '');
        $('#supplementForm').attr('action', SUPP_URL.replace(':id', $b.data('quote-id')));
    });
</script>
@endsection
