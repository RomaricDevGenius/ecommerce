@extends('backend.layouts.app')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #pickup-map {
            width: 100%;
            height: 320px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        #pickup-search-wrapper {
            position: relative;
        }
        #pickup-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 9999;
            max-height: 220px;
            overflow-y: auto;
            display: none;
            border: 1px solid #dee2e6;
            border-radius: 0 0 6px 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,.12);
        }
        #pickup-search-results .list-group-item {
            font-size: 13px;
            padding: 8px 12px;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>

    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Payment Configuration') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('business_settings.update') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="delivery_boy_payment_type">

                            <label class="col-md-4 col-from-label">
                                {{ translate('Monthly Earnings') }}
                            </label>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="radio" name="delivery_boy_payment_type" value="salary"
                                        @if (get_setting('delivery_boy_payment_type') == 'salary') checked @endif>
                                    <span></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row" id="salary_div" style="display: none;">
                            <label class="col-sm-4 col-from-label">{{ translate('Salary Amount') }}</label>
                            <div class="col-sm-8">
                                <input type="hidden" name="types[]" value="delivery_boy_salary">
                                <div class="input-group">
                                    <input type="number" name="delivery_boy_salary" class="form-control"
                                        value="{{ get_setting('delivery_boy_salary') ? get_setting('delivery_boy_salary') : '0' }}">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="inputGroupPrepend">
                                            {{ \App\Models\Currency::find(get_setting('system_default_currency'))->code }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-md-4 col-from-label">
                                {{ translate('Per Order Commission') }}
                            </label>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="radio" name="delivery_boy_payment_type" value="commission"
                                        @if (get_setting('delivery_boy_payment_type') == 'commission') checked @endif>
                                    <span></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row" id="commission_div" style="display: none;">
                            <label class="col-sm-4 col-from-label">{{ translate('Commission Rate') }}</label>
                            <div class="col-sm-8">
                                <input type="hidden" name="types[]" value="delivery_boy_commission">
                                <div class="input-group">
                                    <input type="number" name="delivery_boy_commission" class="form-control"
                                        value="{{ get_setting('delivery_boy_commission') ? get_setting('delivery_boy_commission') : '0' }}">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="inputGroupPrepend">
                                            {{ \App\Models\Currency::find(get_setting('system_default_currency'))->code }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="minimum_delivery_boy_withdraw_amount">
                            <label class="col-sm-4 col-from-label">{{ translate('Minimum Withdrawal Amount') }}</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" min="0" step="1" name="minimum_delivery_boy_withdraw_amount"
                                           class="form-control"
                                           value="{{ get_setting('minimum_delivery_boy_withdraw_amount') ?? 500 }}">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            {{ \App\Models\Currency::find(get_setting('system_default_currency'))->code ?? 'XOF' }}
                                        </span>
                                    </div>
                                </div>
                                <small class="text-muted">{{ translate('Minimum amount a delivery boy can request to withdraw.') }}</small>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Notification Configuration') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('business_settings.update') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="delivery_boy_mail_notification">

                            <label class="col-md-4 col-from-label">
                                {{ translate('Send Mail') }}
                            </label>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" name="delivery_boy_mail_notification" value="1"
                                        @if (get_setting('delivery_boy_mail_notification') == '1') checked @endif>
                                    <span></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="delivery_boy_otp_notification">

                            <label class="col-md-4 col-from-label">
                                {{ translate('Send OTP') }}
                            </label>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" name="delivery_boy_otp_notification" value="1"
                                        @if (get_setting('delivery_boy_otp_notification') == '1') checked @endif>
                                    <span></span>
                                </label>
                            </div>
                        </div>
                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Pickup Location For Delivery Boy') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('business_settings.update') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        {{-- ── Sélecteur de point de ramassage OpenStreetMap ── --}}
                        <div class="form-group row">
                            <div class="col-12" id="pickup-search-wrapper">
                                <div class="input-group">
                                    <input type="text" id="pickup-search" class="form-control"
                                        placeholder="{{ translate('Search an address…') }}">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="pickup-search-btn">
                                            <i class="la la-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div id="pickup-search-results" class="list-group bg-white"></div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-12">
                                <div id="pickup-map"></div>
                                <small class="text-muted">
                                    {{ translate('Click on the map or drag the marker to set the pickup point.') }}
                                </small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-md-2 col-form-label">Latitude</label>
                            <div class="col-md-10">
                                <input type="hidden" name="types[]" value="delivery_pickup_latitude">
                                <input type="text" class="form-control" id="pickup-lat"
                                    name="delivery_pickup_latitude" readonly
                                    value="{{ get_setting('delivery_pickup_latitude') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-2 col-form-label">Longitude</label>
                            <div class="col-md-10">
                                <input type="hidden" name="types[]" value="delivery_pickup_longitude">
                                <input type="text" class="form-control" id="pickup-lng"
                                    name="delivery_pickup_longitude" readonly
                                    value="{{ get_setting('delivery_pickup_longitude') }}">
                            </div>
                        </div>
                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        (function($) {
            "use strict";
            $(document).ready(function() {
                show_hide_div();
            })

            $("[name=delivery_boy_payment_type]").on("change", function() {
                show_hide_div();
            });

            function show_hide_div() {
                $("#salary_div").hide();
                $("#commission_div").hide();
                if ($("[name=delivery_boy_payment_type]:checked").val() == 'salary') {
                    $("#salary_div").show();
                }
                if ($("[name=delivery_boy_payment_type]:checked").val() == 'commission') {
                    $("#commission_div").show();
                }
            }
        })(jQuery);
    </script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    (function () {
        var savedLat = parseFloat('{{ get_setting("delivery_pickup_latitude") ?: "12.3714" }}');
        var savedLng = parseFloat('{{ get_setting("delivery_pickup_longitude") ?: "-1.5197" }}');
        var hasPin   = {{ get_setting('delivery_pickup_latitude') ? 'true' : 'false' }};

        var map = L.map('pickup-map').setView([savedLat, savedLng], hasPin ? 15 : 13);

        var cartoLayer = L.tileLayer('https://a.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors © CARTO',
            maxZoom: 19
        }).addTo(map);

        var satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '© Esri, Maxar, Earthstar Geographics',
            maxZoom: 19
        });

        var isSatellite = false;
        var satBtn = document.createElement('button');
        satBtn.type = 'button';
        satBtn.textContent = '🛰 {{ translate("Satellite") }}';
        satBtn.style.cssText = 'position:absolute;bottom:10px;right:10px;z-index:1000;background:white;border:1px solid #aaa;border-radius:6px;padding:4px 10px;font-size:12px;font-weight:600;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,.2);';
        document.getElementById('pickup-map').style.position = 'relative';
        document.getElementById('pickup-map').appendChild(satBtn);
        satBtn.addEventListener('click', function () {
            isSatellite = !isSatellite;
            if (isSatellite) { map.removeLayer(cartoLayer); satelliteLayer.addTo(map); satBtn.textContent = '🗺 {{ translate("Plan") }}'; }
            else { map.removeLayer(satelliteLayer); cartoLayer.addTo(map); satBtn.textContent = '🛰 {{ translate("Satellite") }}'; }
        });

        var marker = L.marker([savedLat, savedLng], { draggable: true });
        if (hasPin) marker.addTo(map);

        function updateFields(lat, lng) {
            document.getElementById('pickup-lat').value = lat.toFixed(6);
            document.getElementById('pickup-lng').value = lng.toFixed(6);
        }

        if (hasPin) updateFields(savedLat, savedLng);

        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            if (!map.hasLayer(marker)) marker.addTo(map);
            updateFields(e.latlng.lat, e.latlng.lng);
        });

        marker.on('dragend', function () {
            var pos = marker.getLatLng();
            updateFields(pos.lat, pos.lng);
        });

        // ── Nominatim search ────────────────────────────────────────────────
        var searchInput   = document.getElementById('pickup-search');
        var searchResults = document.getElementById('pickup-search-results');
        var searchTimer;

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            var q = this.value.trim();
            if (q.length < 3) { searchResults.style.display = 'none'; return; }
            searchTimer = setTimeout(function () { doSearch(q); }, 500);
        });

        document.getElementById('pickup-search-btn').addEventListener('click', function () {
            var q = searchInput.value.trim();
            if (q.length >= 3) doSearch(q);
        });

        function doSearch(q) {
            fetch('https://nominatim.openstreetmap.org/search?q=' + encodeURIComponent(q) +
                  '&format=json&limit=5&countrycodes=bf&viewbox=-5.5,15.1,2.4,9.4&bounded=1', {
                headers: { 'User-Agent': 'DakwariAdmin/1.0 (contact@dakwari.com)' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                searchResults.innerHTML = '';
                if (!data.length) { searchResults.style.display = 'none'; return; }
                data.forEach(function (item) {
                    var a = document.createElement('a');
                    a.className = 'list-group-item list-group-item-action';
                    a.href = '#';
                    a.title = item.display_name;
                    a.textContent = item.display_name;
                    a.addEventListener('click', function (e) {
                        e.preventDefault();
                        var lat = parseFloat(item.lat);
                        var lng = parseFloat(item.lon);
                        map.setView([lat, lng], 16);
                        marker.setLatLng([lat, lng]);
                        if (!map.hasLayer(marker)) marker.addTo(map);
                        updateFields(lat, lng);
                        searchInput.value = item.display_name;
                        searchResults.style.display = 'none';
                    });
                    searchResults.appendChild(a);
                });
                searchResults.style.display = 'block';
            })
            .catch(function () {});
        }

        document.addEventListener('click', function (e) {
            if (!searchResults.contains(e.target) && e.target !== searchInput) {
                searchResults.style.display = 'none';
            }
        });
    })();
    </script>
@endsection
