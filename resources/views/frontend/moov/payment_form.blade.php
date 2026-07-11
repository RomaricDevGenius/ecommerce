@extends('frontend.layouts.app')

@section('content')
<section class="pt-5 mb-4">
    <div class="container">
        <div class="row">
            <div class="col-xl-8 mx-auto">
                <div class="row aiz-steps arrow-divider">
                    <div class="col done"><div class="text-center text-success"><i class="la-3x mb-2 las la-shopping-cart"></i><h3 class="fs-14 fw-600 d-none d-lg-block">{{ translate('1. My Cart') }}</h3></div></div>
                    <div class="col done"><div class="text-center text-success"><i class="la-3x mb-2 las la-map"></i><h3 class="fs-14 fw-600 d-none d-lg-block">{{ translate('2. Shipping info') }}</h3></div></div>
                    <div class="col done"><div class="text-center text-success"><i class="la-3x mb-2 las la-truck"></i><h3 class="fs-14 fw-600 d-none d-lg-block">{{ translate('3. Delivery info') }}</h3></div></div>
                    <div class="col active"><div class="text-center text-primary"><i class="la-3x mb-2 las la-credit-card"></i><h3 class="fs-14 fw-600 d-none d-lg-block">{{ translate('4. Payment') }}</h3></div></div>
                    <div class="col"><div class="text-center"><i class="la-3x mb-2 opacity-50 las la-check-circle"></i><h3 class="fs-14 fw-600 d-none d-lg-block opacity-50">{{ translate('5. Confirmation') }}</h3></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-4">
    <div class="container text-left">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 rounded">
                    <div class="card-header p-3 d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                        <h3 class="fs-16 fw-600 mb-0">{{ translate('Pay by') }} Moov Money</h3>
                        <span class="badge badge-inline badge-primary fs-13 fw-600" style="white-space:nowrap;">
                            {{ str_replace('{amount}', number_format($combined_order->grand_total, 0, '', ' '), translate('You owe {amount} FCFA')) }}
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="alert alert-danger d-none" id="error-msg"></div>
                        <div class="alert alert-success d-none" id="success-msg"></div>

                        {{-- ── Étape 1 : saisie du numéro ── --}}
                        <div id="step-phone">
                            <p class="text-muted mb-3">{{ translate('Enter your Moov Money phone number. You will receive an OTP code by SMS to confirm the payment.') }}</p>
                            <div class="form-group">
                                <label class="fw-600">{{ translate('Moov Money phone number') }}</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">+226</span>
                                    </div>
                                    <input type="text" id="phone_number" class="form-control"
                                        placeholder="70 12 34 56" maxlength="8">
                                </div>
                                <small class="text-muted">{{ translate('8 digits, no spaces or dashes') }}</small>
                            </div>
                            <button type="button" class="btn btn-primary fw-600 d-inline-flex align-items-center justify-content-center" id="btn-request-otp" disabled>
                                <span id="spinner-request-otp" class="spinner-border spinner-border-sm mr-2 d-none" role="status" aria-hidden="true"></span>
                                <i class="las la-sms mr-1" id="icon-request-otp"></i>{{ translate('Receive OTP code') }}
                            </button>
                        </div>

                        {{-- ── Étape 2 : saisie de l'OTP ── --}}
                        <div id="step-otp" class="d-none">
                            <div class="alert alert-info">
                                <i class="las la-check-circle mr-1"></i>
                                {{ translate('OTP code sent to') }} <strong id="display-phone"></strong>. {{ translate('Valid for 10 minutes.') }}
                            </div>
                            <div class="form-group">
                                <label class="fw-600">{{ translate('OTP code received by SMS') }}</label>
                                <input type="text" id="otp_code" class="form-control" placeholder="Ex : 464717" maxlength="8">
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center" id="btn-resend-otp">
                                    <span id="spinner-resend-otp" class="spinner-border spinner-border-sm mr-2 d-none" role="status" aria-hidden="true"></span>
                                    <i class="las la-redo-alt mr-1" id="icon-resend-otp"></i>{{ translate('Resend code') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-link text-muted" id="btn-change-phone">
                                    {{ translate('Change number') }}
                                </button>
                            </div>
                            <button type="button" class="btn btn-primary fw-600 btn-block d-inline-flex align-items-center justify-content-center" id="btn-confirm">
                                <span id="spinner-confirm" class="spinner-border spinner-border-sm mr-2 d-none" role="status" aria-hidden="true"></span>
                                <i class="las la-paper-plane mr-1" id="icon-confirm"></i>{{ translate('Confirm payment') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row align-items-center pt-3">
                    <div class="col-6">
                        <a href="{{ route('home') }}" class="link link--style-3">
                            <i class="las la-arrow-left"></i> {{ translate('Return to shop') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
$(function () {
    var transId   = '';
    var requestId = '';

    var GENERATE_URL = '{{ route('moov.pay') }}';
    var CONFIRM_URL  = '{{ route('moov.confirm') }}';
    var CSRF         = '{{ csrf_token() }}';

    function showError(msg)   { $('#error-msg').text(msg).removeClass('d-none'); $('#success-msg').addClass('d-none'); }
    function showSuccess(msg) { $('#success-msg').text(msg).removeClass('d-none'); $('#error-msg').addClass('d-none'); }
    function clearAlerts()    { $('#error-msg, #success-msg').addClass('d-none'); }

    function openLoader()  { /* .aiz-refresh gère l'overlay via ajaxStart */ }
    function closeLoader() { btnStop(); }

    // Spinner intégré au bouton cliqué (comme Coris)
    var _lastBtn = null;
    function btnStart(btnId, spinnerId, iconId) {
        _lastBtn = { b: btnId, s: spinnerId, i: iconId };
        $('#' + btnId).prop('disabled', true);
        $('#' + spinnerId).removeClass('d-none');
        $('#' + iconId).addClass('d-none');
    }
    function btnStop() {
        if (!_lastBtn) return;
        $('#' + _lastBtn.b).prop('disabled', false);
        $('#' + _lastBtn.s).addClass('d-none');
        $('#' + _lastBtn.i).removeClass('d-none');
        _lastBtn = null;
    }

    // Validation téléphone
    $('#phone_number').on('input', function () {
        var val = $(this).val().replace(/\D/g, '');
        $(this).val(val);
        $('#btn-request-otp').prop('disabled', val.length < 8);
    });

    // Étape 1 → demande OTP
    $('#btn-request-otp').on('click', function () {
        clearAlerts();
        openLoader();
        btnStart('btn-request-otp', 'spinner-request-otp', 'icon-request-otp');
        $.post(GENERATE_URL, {
            _token:       CSRF,
            phone_number: $('#phone_number').val(),
        })
        .done(function (data) {
            closeLoader();
            if (data.success) {
                transId   = data.trans_id;
                requestId = data.request_id;
                $('#display-phone').text('+226 ' + $('#phone_number').val());
                $('#step-phone').addClass('d-none');
                $('#step-otp').removeClass('d-none');
                showSuccess(data.message);
            } else {
                showError(data.message || "{{ translate('An unexpected error occurred, please try again later') }}");
            }
        })
        .fail(function () {
            closeLoader();
            showError("{{ translate('An unexpected error occurred, please try again later') }}");
        });
    });

    // Renvoi OTP
    $('#btn-resend-otp').on('click', function () {
        clearAlerts();
        openLoader();
        btnStart('btn-resend-otp', 'spinner-resend-otp', 'icon-resend-otp');
        $.post(GENERATE_URL, {
            _token:       CSRF,
            phone_number: $('#phone_number').val(),
        })
        .done(function (data) {
            closeLoader();
            if (data.success) {
                transId   = data.trans_id;
                requestId = data.request_id;
                showSuccess(data.message);
            } else {
                showError(data.message || "{{ translate('An unexpected error occurred, please try again later') }}");
            }
        })
        .fail(function () {
            closeLoader();
            showError("{{ translate('An unexpected error occurred, please try again later') }}");
        });
    });

    // Changer de numéro
    $('#btn-change-phone').on('click', function () {
        clearAlerts();
        $('#step-otp').addClass('d-none');
        $('#step-phone').removeClass('d-none');
        $('#otp_code').val('');
    });

    // Étape 2 → confirme paiement
    $('#btn-confirm').on('click', function () {
        var otp = $('#otp_code').val().trim();
        if (!otp) {
            showError("{{ translate('Please enter the OTP code received by SMS') }}");
            return;
        }
        clearAlerts();
        openLoader();
        btnStart('btn-confirm', 'spinner-confirm', 'icon-confirm');
        $.post(CONFIRM_URL, {
            _token:       CSRF,
            phone_number: $('#phone_number').val(),
            otp:          otp,
            trans_id:     transId,
            request_id:   requestId,
        })
        .done(function (data) {
            closeLoader();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Paiement effectué avec succès',
                    text: data.message,
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                }).then(function () {
                    if (data.url) window.location.href = data.url;
                });
            } else {
                showError(data.message || "{{ translate('An unexpected error occurred, please try again later') }}");
            }
        })
        .fail(function () {
            closeLoader();
            showError("{{ translate('An unexpected error occurred, please try again later') }}");
        });
    });
});
</script>
@endsection
