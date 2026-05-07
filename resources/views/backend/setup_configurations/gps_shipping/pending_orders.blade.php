@extends('backend.layouts.app')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    /* ── Modal centré même avec sidebar ── */
    #supplementModal.modal { padding-left: 0 !important; }
    #supplementModal .modal-dialog {
        margin: 40px auto;
        max-width: 720px;
    }

    /* ── Carte ── */
    #quote-map {
        height: 260px;
        width: 100%;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        background: #f5f5f5;
    }

    /* ── Infos client / distance ── */
    .quote-info-box {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 14px 18px;
        margin: 14px 0;
        display: flex;
        gap: 32px;
    }
    .quote-info-box .info-item label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #888;
        margin-bottom: 2px;
        display: block;
    }
    .quote-info-box .info-item strong {
        font-size: 15px;
        color: #2d3748;
    }

    /* ── Badges tableau ── */
    .gps-badge-dist {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #eef2ff;
        color: #4f46e5;
        border: 1px solid #c7d2fe;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }
    .gps-badge-pending {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }
    .gps-badge-confirmed {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }
    .gps-badge-pending::before {
        content: '';
        display: inline-block;
        width: 7px; height: 7px;
        border-radius: 50%;
        background: #fb923c;
    }
    .gps-badge-confirmed::before {
        content: '';
        display: inline-block;
        width: 7px; height: 7px;
        border-radius: 50%;
        background: #22c55e;
    }

    /* ── Bouton Fixer ── */
    .btn-fix-quote {
        white-space: nowrap;
        font-size: 12px;
        padding: 5px 12px;
        border-radius: 6px;
    }

    /* ── Map legend ── */
    .map-legend {
        display: flex;
        gap: 16px;
        margin-top: 6px;
        font-size: 11px;
        color: #666;
    }
    .map-legend span { display: flex; align-items: center; gap: 5px; }
    .dot-store  { width:10px;height:10px;border-radius:50%;background:#22c55e;display:inline-block; }
    .dot-client { width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block; }
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
                                <th class="text-right" style="min-width:110px">{{ translate('Action') }}</th>
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
                                    <span class="text-dark">{{ $quote->created_at->format('d/m/Y') }}</span><br>
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
                                        class="btn btn-soft-warning btn-fix-quote"
                                        data-quote-id="{{ $quote->id }}"
                                        data-client="{{ $quote->user ? $quote->user->name : '?' }}"
                                        data-distance="{{ number_format($quote->distance_km, 1) }}"
                                        data-current="{{ $quote->supplement_amount }}"
                                        data-lat="{{ $quote->delivery_lat }}"
                                        data-lng="{{ $quote->delivery_lng }}"
                                        data-toggle="modal" data-target="#supplementModal"
                                        title="{{ translate('Fixer le devis') }}">
                                        <i class="las la-money-bill-wave mr-1"></i>{{ $quote->supplement_amount ? translate('Modifier') : translate('Fixer') }}
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
            <div class="card-footer">
                {{ $quotes->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Modal supplément ─────────────────────────────────────────────── --}}
<div class="modal fade" id="supplementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
            <form id="supplementForm" method="POST" action="">
                @csrf

                {{-- Header --}}
                <div class="modal-header" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border:none;padding:18px 24px">
                    <div>
                        <h5 class="modal-title h6 text-white mb-0">
                            <i class="las la-route mr-2"></i>{{ translate('Fixer le devis de livraison') }}
                        </h5>
                        <small class="text-white" style="opacity:.75">{{ translate('Définissez le montant à proposer au client') }}</small>
                    </div>
                    <button type="button" class="close text-white" data-dismiss="modal" style="opacity:.8;font-size:22px">&times;</button>
                </div>

                {{-- Body --}}
                <div class="modal-body" style="padding:24px">

                    {{-- Carte --}}
                    <div id="quote-map"></div>
                    <div class="map-legend">
                        <span><i class="dot-store"></i> Boutique (départ)</span>
                        <span><i class="dot-client"></i> Client (arrivée)</span>
                    </div>

                    {{-- Infos --}}
                    <div class="quote-info-box">
                        <div class="info-item">
                            <label><i class="las la-user mr-1"></i>{{ translate('Client') }}</label>
                            <strong id="modal-client">—</strong>
                        </div>
                        <div class="info-item">
                            <label><i class="las la-route mr-1"></i>{{ translate('Distance') }}</label>
                            <strong><span id="modal-distance">—</span> km</strong>
                        </div>
                    </div>

                    {{-- Montant --}}
                    <div class="form-group mb-0">
                        <label class="font-weight-600" style="font-size:13px">
                            {{ translate('Frais de livraison') }}
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="number" name="supplement" id="modal-supplement-input"
                                class="form-control" min="0" step="1"
                                placeholder="Ex: 3500" required
                                style="border-radius:8px 0 0 8px;font-size:16px;font-weight:600">
                            <div class="input-group-append">
                                <span class="input-group-text" style="border-radius:0 8px 8px 0;background:#667eea;color:#fff;font-weight:600;border-color:#667eea">FCFA</span>
                            </div>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="las la-info-circle mr-1"></i>{{ translate('Ce montant sera envoyé au client par push et email.') }}
                        </small>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:8px;padding:8px 20px">
                        {{ translate('Annuler') }}
                    </button>
                    <button type="submit" class="btn btn-primary" style="border-radius:8px;padding:8px 24px;background:#667eea;border-color:#667eea">
                        <i class="las la-paper-plane mr-1"></i>{{ translate('Envoyer au client') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    var storeLat = {{ (float) get_setting('delivery_pickup_latitude', '12.3714') }};
    var storeLng = {{ (float) get_setting('delivery_pickup_longitude', '-1.5197') }};
    var supplementBaseUrl = "{{ route('gps_shipping.supplement', ':id') }}";
    var quoteMap = null;
    var pendingMapData = null;

    // Event delegation — survit aux re-renders de DataTables
    $(document).on('click', '.btn-fix-quote', function () {
        var $btn    = $(this);
        var quoteId = $btn.data('quote-id');

        $('#modal-client').text($btn.data('client') || '—');
        $('#modal-distance').text($btn.data('distance') || '?');
        $('#modal-supplement-input').val($btn.data('current') || '');
        $('#supplementForm').attr('action', supplementBaseUrl.replace(':id', quoteId));

        pendingMapData = {
            lat    : parseFloat($btn.data('lat')),
            lng    : parseFloat($btn.data('lng')),
            client : $btn.data('client') || ''
        };
    });

    // Initialiser la carte APRÈS que le modal est complètement visible (animation terminée)
    $('#supplementModal').on('shown.bs.modal', function () {
        if (!pendingMapData) return;

        if (quoteMap) { quoteMap.remove(); quoteMap = null; }

        quoteMap = L.map('quote-map', { zoomControl: true, attributionControl: true });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 18
        }).addTo(quoteMap);

        var storeIcon = L.divIcon({
            className: '',
            html: '<div style="background:#22c55e;width:16px;height:16px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35)"></div>',
            iconAnchor: [8, 8]
        });
        var clientIcon = L.divIcon({
            className: '',
            html: '<div style="background:#ef4444;width:16px;height:16px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35)"></div>',
            iconAnchor: [8, 8]
        });

        L.marker([storeLat, storeLng], { icon: storeIcon })
            .addTo(quoteMap)
            .bindPopup('<strong>Boutique</strong><br>Point de départ');

        if (!isNaN(pendingMapData.lat) && !isNaN(pendingMapData.lng)) {
            L.marker([pendingMapData.lat, pendingMapData.lng], { icon: clientIcon })
                .addTo(quoteMap)
                .bindPopup('<strong>' + pendingMapData.client + '</strong><br>Lieu de livraison');

            L.polyline([[storeLat, storeLng], [pendingMapData.lat, pendingMapData.lng]], {
                color: '#667eea', weight: 3, dashArray: '8 6', opacity: .85
            }).addTo(quoteMap);

            quoteMap.fitBounds(
                [[storeLat, storeLng], [pendingMapData.lat, pendingMapData.lng]],
                { padding: [30, 30] }
            );
        } else {
            quoteMap.setView([storeLat, storeLng], 12);
        }

        // Force Leaflet à recalculer la taille du conteneur
        quoteMap.invalidateSize();
    });

    $('#supplementModal').on('hidden.bs.modal', function () {
        if (quoteMap) { quoteMap.remove(); quoteMap = null; }
        pendingMapData = null;
    });
</script>
@endsection
