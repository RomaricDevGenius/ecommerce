@extends('backend.layouts.app')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    /* ── Badges tableau ── */
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

    /* ── Boutons action ── */
    .btn-map-quote, .btn-fix-quote { white-space: nowrap; font-size: 12px; }

    /* ── Modal carte ── */
    #mapModal.modal            { padding-left: 0 !important; }
    #mapModal .modal-dialog    { max-width: 860px; margin: 30px auto; }
    #mapModal .modal-content   { border: none; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.18); }
    #route-map                 { height: 440px; width: 100%; }

    .map-stat-card {
        background: #f8f9fc; border-radius: 8px;
        padding: 12px 18px; text-align: center;
    }
    .map-stat-card .label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing:.5px; }
    .map-stat-card .value { font-size: 18px; font-weight: 700; color: #2d3748; margin-top: 2px; }

    .dot-store  { display:inline-block; width:10px; height:10px; border-radius:50%; background:#22c55e; margin-right:4px; }
    .dot-client { display:inline-block; width:10px; height:10px; border-radius:50%; background:#ef4444; margin-right:4px; }

    /* ── Modal formulaire ── */
    #supplementModal.modal          { padding-left: 0 !important; }
    #supplementModal .modal-dialog  { max-width: 440px; margin: 80px auto; }
    #supplementModal .modal-content { border: none; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.18); }
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
                                <th class="text-right" style="min-width:160px">{{ translate('Actions') }}</th>
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
                                    {{-- Bouton Voir trajet --}}
                                    <button type="button"
                                        class="btn btn-soft-info btn-sm btn-map-quote mr-1"
                                        data-client="{{ $quote->user ? $quote->user->name : '?' }}"
                                        data-distance="{{ number_format($quote->distance_km, 1) }}"
                                        data-lat="{{ $quote->delivery_lat }}"
                                        data-lng="{{ $quote->delivery_lng }}"
                                        data-toggle="modal" data-target="#mapModal"
                                        title="{{ translate('Voir le trajet') }}">
                                        <i class="las la-map-marked-alt mr-1"></i>Trajet
                                    </button>
                                    {{-- Bouton Fixer le prix --}}
                                    <button type="button"
                                        class="btn btn-soft-warning btn-sm btn-fix-quote"
                                        data-quote-id="{{ $quote->id }}"
                                        data-client="{{ $quote->user ? $quote->user->name : '?' }}"
                                        data-distance="{{ number_format($quote->distance_km, 1) }}"
                                        data-current="{{ $quote->supplement_amount }}"
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
            <div class="card-footer">{{ $quotes->links() }}</div>
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     MODAL CARTE — dédié, grand, propre
═══════════════════════════════════════════════ --}}
<div class="modal fade" id="mapModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header" style="background:linear-gradient(135deg,#1a73e8 0%,#0d47a1 100%);border:none;padding:16px 22px">
                <div>
                    <h5 class="modal-title h6 text-white mb-0">
                        <i class="las la-map-marked-alt mr-2"></i>{{ translate('Trajet de livraison') }}
                    </h5>
                    <small class="text-white" style="opacity:.8">
                        <span class="dot-store" style="background:#4ade80"></span>Boutique →
                        <span class="dot-client" style="background:#f87171;margin-left:4px"></span><span id="map-header-client">Client</span>
                    </small>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" style="opacity:.85;font-size:22px">&times;</button>
            </div>

            {{-- Carte --}}
            <div id="route-map"></div>

            {{-- Stats sous la carte --}}
            <div class="modal-body" style="padding:16px 22px">
                <div class="row">
                    <div class="col-4">
                        <div class="map-stat-card">
                            <div class="label"><i class="las la-user mr-1"></i>{{ translate('Client') }}</div>
                            <div class="value" id="map-stat-client" style="font-size:15px">—</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="map-stat-card">
                            <div class="label"><i class="las la-route mr-1"></i>{{ translate('Distance estimée') }}</div>
                            <div class="value" id="map-stat-distance">— km</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="map-stat-card">
                            <div class="label"><i class="las la-info-circle mr-1"></i>{{ translate('Zone') }}</div>
                            <div class="value text-warning" style="font-size:14px">{{ translate('Révision manuelle') }}</div>
                        </div>
                    </div>
                </div>
                <div class="mt-2 d-flex align-items-center" style="font-size:12px;color:#888">
                    <i class="dot-store" style="background:#22c55e;flex-shrink:0"></i>
                    <span class="mr-3">{{ translate('Boutique (départ)') }}</span>
                    <i class="dot-client" style="background:#ef4444;flex-shrink:0"></i>
                    <span>{{ translate('Lieu de livraison (arrivée)') }}</span>
                </div>
            </div>

            <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:12px 22px">
                <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Fermer') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     MODAL FORMULAIRE — simple, ciblé
═══════════════════════════════════════════════ --}}
<div class="modal fade" id="supplementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="supplementForm" method="POST" action="">
                @csrf

                {{-- Header --}}
                <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);border:none;padding:16px 22px">
                    <div>
                        <h5 class="modal-title h6 text-white mb-0">
                            <i class="las la-money-bill-wave mr-2"></i>{{ translate('Fixer le devis') }}
                        </h5>
                        <small class="text-white" style="opacity:.85" id="form-header-info">—</small>
                    </div>
                    <button type="button" class="close text-white" data-dismiss="modal" style="opacity:.85;font-size:22px">&times;</button>
                </div>

                {{-- Corps --}}
                <div class="modal-body" style="padding:24px">

                    {{-- Résumé client --}}
                    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;margin-bottom:20px">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">{{ translate('Client') }}</small>
                                <strong id="form-client">—</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">{{ translate('Distance') }}</small>
                                <strong><span id="form-distance">—</span> km</strong>
                            </div>
                        </div>
                    </div>

                    {{-- Champ montant --}}
                    <div class="form-group mb-1">
                        <label class="font-weight-600" style="font-size:13px;color:#374151">
                            {{ translate('Montant des frais de livraison') }}
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="number" name="supplement" id="modal-supplement-input"
                                class="form-control" min="0" step="1"
                                placeholder="Ex: 3500" required
                                style="border-radius:8px 0 0 8px;font-size:18px;font-weight:700;border-color:#d1d5db">
                            <div class="input-group-append">
                                <span class="input-group-text"
                                    style="border-radius:0 8px 8px 0;background:#f59e0b;color:#fff;font-weight:700;border-color:#f59e0b;font-size:14px">
                                    FCFA
                                </span>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">
                        <i class="las la-paper-plane mr-1"></i>{{ translate('Envoyé au client par push et email.') }}
                    </small>
                </div>

                {{-- Footer --}}
                <div class="modal-footer" style="border-top:1px solid #f3f4f6;padding:14px 24px">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:8px">
                        {{ translate('Annuler') }}
                    </button>
                    <button type="submit" class="btn btn-warning text-white" style="border-radius:8px;padding:8px 24px;font-weight:600">
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
    var STORE_LAT = {{ (float) get_setting('delivery_pickup_latitude',  '12.3714') }};
    var STORE_LNG = {{ (float) get_setting('delivery_pickup_longitude', '-1.5197') }};
    var SUPP_URL  = "{{ route('gps_shipping.supplement', ':id') }}";
    var routeMap  = null;

    /* ── Bouton "Trajet" : prépare les données du modal carte ── */
    $(document).on('click', '.btn-map-quote', function () {
        var $b = $(this);
        $('#map-header-client').text($b.data('client') || '?');
        $('#map-stat-client').text($b.data('client') || '—');
        $('#map-stat-distance').text(($b.data('distance') || '?') + ' km');

        // stocké pour shown.bs.modal
        $('#mapModal').data('lat',    parseFloat($b.data('lat')));
        $('#mapModal').data('lng',    parseFloat($b.data('lng')));
        $('#mapModal').data('client', $b.data('client') || '');
    });

    /* ── Initialise la carte une fois le modal entièrement visible ── */
    $('#mapModal').on('shown.bs.modal', function () {
        var lat    = $(this).data('lat');
        var lng    = $(this).data('lng');
        var client = $(this).data('client');

        if (routeMap) { routeMap.remove(); routeMap = null; }

        routeMap = L.map('route-map', { zoomControl: true });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 18
        }).addTo(routeMap);

        /* Marqueur boutique */
        var storeIcon = L.divIcon({
            className: '',
            html: '<div style="width:18px;height:18px;border-radius:50%;background:#22c55e;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.35)"></div>',
            iconAnchor: [9, 9]
        });
        L.marker([STORE_LAT, STORE_LNG], { icon: storeIcon })
            .addTo(routeMap)
            .bindPopup('<strong>Boutique</strong><br>Point de départ').openPopup();

        /* Marqueur client */
        if (!isNaN(lat) && !isNaN(lng)) {
            var clientIcon = L.divIcon({
                className: '',
                html: '<div style="width:18px;height:18px;border-radius:50%;background:#ef4444;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.35)"></div>',
                iconAnchor: [9, 9]
            });
            L.marker([lat, lng], { icon: clientIcon })
                .addTo(routeMap)
                .bindPopup('<strong>' + client + '</strong><br>Lieu de livraison');

            /* Ligne de trajet */
            L.polyline([[STORE_LAT, STORE_LNG], [lat, lng]], {
                color: '#1a73e8', weight: 3, dashArray: '10 6', opacity: .9
            }).addTo(routeMap);

            routeMap.fitBounds([[STORE_LAT, STORE_LNG], [lat, lng]], { padding: [40, 40] });
        } else {
            routeMap.setView([STORE_LAT, STORE_LNG], 12);
        }

        routeMap.invalidateSize();
    });

    /* ── Nettoyage à la fermeture ── */
    $('#mapModal').on('hidden.bs.modal', function () {
        if (routeMap) { routeMap.remove(); routeMap = null; }
    });

    /* ── Bouton "Fixer" : remplit le formulaire ── */
    $(document).on('click', '.btn-fix-quote', function () {
        var $b = $(this);
        $('#form-client').text($b.data('client') || '—');
        $('#form-distance').text($b.data('distance') || '?');
        $('#modal-supplement-input').val($b.data('current') || '');
        $('#form-header-info').text(($b.data('client') || '') + ' — ' + ($b.data('distance') || '?') + ' km');
        $('#supplementForm').attr('action', SUPP_URL.replace(':id', $b.data('quote-id')));
    });
</script>
@endsection
