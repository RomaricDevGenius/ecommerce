@extends('frontend.layouts.app')

@section('content')
    <section class="py-4 gry-bg">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                            <h3 class="h6 mb-0">{{ translate('Pay by') }} Coris Money</h3>
                            <span class="badge badge-inline badge-primary fs-13 fw-600" style="white-space:nowrap;">
                                {{ str_replace('{amount}', number_format($combined_order->grand_total, 0, '', ' '), translate('You owe {amount} FCFA')) }}
                            </span>
                        </div>
                        <div class="card-body">

                            {{-- ÉTAPE 1 : Saisie du numéro de téléphone --}}
                            <div id="coris-step-1">
                                <p class="mb-3 fs-13">
                                    Entrez votre numéro de téléphone Coris Money pour recevoir votre code OTP.
                                </p>

                                <div class="form-group">
                                    <label class="form-label">{{ translate('Coris Money phone number') }}</label>
                                    <input type="text"
                                           class="form-control"
                                           id="coris_phone_number"
                                           placeholder="{{ translate('Phone number linked to Coris Money account') }}"
                                           required>
                                </div>

                                <div class="form-group text-right">
                                    <button type="button"
                                            class="btn btn-primary fw-600 d-inline-flex align-items-center justify-content-center"
                                            id="btn_send_otp">
                                        <span id="spinner_send_otp" class="spinner-border spinner-border-sm mr-2 d-none" role="status" aria-hidden="true"></span>
                                        <span id="text_send_otp">Recevoir le code OTP</span>
                                    </button>
                                </div>

                                <div id="msg_step1" class="mt-3 d-none">
                                    <div class="alert mb-0"></div>
                                </div>
                            </div>

                            {{-- ÉTAPE 2 : Saisie du code OTP (masquée au départ) --}}
                            <div id="coris-step-2" class="d-none">
                                <div class="alert alert-success mb-3 fs-13">
                                    Code OTP envoyé sur votre téléphone. Veuillez le saisir ci-dessous.
                                </div>

                                <form id="coris-payment-form" method="POST" action="{{ route('coris.pay') }}">
                                    @csrf
                                    <input type="hidden" id="form_phone_number" name="phone_number">

                                    <div class="form-group">
                                        <label class="form-label">Code OTP reçu par SMS</label>
                                        <input type="text"
                                               class="form-control"
                                               name="otp_code"
                                               id="coris_otp_code"
                                               placeholder="Entrez le code OTP"
                                               required>
                                    </div>

                                    <div class="form-group d-flex justify-content-between align-items-center">
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm"
                                                id="btn_back_step1">
                                            Changer de numéro
                                        </button>

                                        <button type="submit"
                                                class="btn btn-primary fw-600 d-inline-flex align-items-center justify-content-center"
                                                id="btn_confirm_payment">
                                            <span id="spinner_confirm" class="spinner-border spinner-border-sm mr-2 d-none" role="status" aria-hidden="true"></span>
                                            <span id="text_confirm">{{ translate('Confirm Payment') }}</span>
                                        </button>
                                    </div>
                                </form>

                                <div id="msg_step2" class="mt-3 d-none">
                                    <div class="alert alert-danger mb-0"></div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<!-- Modal confirmation paiement Coris -->
<div class="modal fade" id="corisSuccessModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#006400; color:#fff;">
                <h5 class="modal-title"><i class="las la-check-circle mr-2"></i>Paiement réussi</h5>
            </div>
            <div class="modal-body" id="corisSuccessBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success fw-600" id="corisSuccessBtn">Continuer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script type="text/javascript">
        $(function () {

            // --- ÉTAPE 1 : Envoi OTP ---
            $('#btn_send_otp').on('click', function () {
                var phone = $('#coris_phone_number').val().trim();
                if (!phone) {
                    showMsg('msg_step1', 'danger', 'Veuillez entrer votre numéro de téléphone.');
                    return;
                }

                hideMsgBox('msg_step1');
                setLoading('btn_send_otp', 'spinner_send_otp', 'text_send_otp', true, '{{ translate('Sending...') }}');

                $.ajax({
                    url: '{{ route('coris.send.otp') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        phone_number: phone
                    },
                    success: function (response) {
                        setLoading('btn_send_otp', 'spinner_send_otp', 'text_send_otp', false, '{{ translate('Receive OTP code') }}');
                        if (response.success) {
                            $('#form_phone_number').val(phone);
                            $('#coris-step-1').addClass('d-none');
                            $('#coris-step-2').removeClass('d-none');
                        } else {
                            showMsg('msg_step1', 'danger', response.message || 'Échec de l\'envoi du code OTP. Veuillez réessayer.');
                        }
                    },
                    error: function () {
                        setLoading('btn_send_otp', 'spinner_send_otp', 'text_send_otp', false, 'Recevoir le code OTP');
                        showMsg('msg_step1', 'danger', 'Une erreur inattendue s\'est produite. Veuillez réessayer.');
                    }
                });
            });

            // --- Retour à l'étape 1 ---
            $('#btn_back_step1').on('click', function () {
                $('#coris-step-2').addClass('d-none');
                $('#coris-step-1').removeClass('d-none');
                hideMsgBox('msg_step1');
                hideMsgBox('msg_step2');
                $('#coris_otp_code').val('');
            });

            // --- ÉTAPE 2 : Confirmation du paiement ---
            $('#coris-payment-form').on('submit', function (e) {
                e.preventDefault();

                var otp = $('#coris_otp_code').val().trim();
                if (!otp) {
                    showMsg('msg_step2', 'danger', 'Veuillez entrer le code OTP.');
                    return;
                }

                var phone = $('#form_phone_number').val();
                hideMsgBox('msg_step2');
                setLoading('btn_confirm_payment', 'spinner_confirm', 'text_confirm', true, '{{ translate('Processing...') }}');

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        phone_number: phone,
                        otp_code: otp
                    },
                    success: function (response) {
                        setLoading('btn_confirm_payment', 'spinner_confirm', 'text_confirm', false, '{{ translate('Confirm Payment') }}');
                        if (response.success && response.url) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Paiement effectué avec succès',
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false,
                            }).then(function () {
                                window.location.href = response.url;
                            });
                        } else {
                            showMsg('msg_step2', 'danger', response.message || 'Paiement échoué. Veuillez réessayer.');
                        }
                    },
                    error: function () {
                        setLoading('btn_confirm_payment', 'spinner_confirm', 'text_confirm', false, '{{ translate('Confirm Payment') }}');
                        showMsg('msg_step2', 'danger', 'Une erreur inattendue s\'est produite. Veuillez réessayer.');
                    }
                });
            });

            // --- Helpers JS ---
            function setLoading(btnId, spinnerId, textId, isLoading, label) {
                $('#' + btnId).prop('disabled', isLoading);
                if (isLoading) {
                    $('#' + spinnerId).removeClass('d-none');
                    $('#' + textId).text(label);
                } else {
                    $('#' + spinnerId).addClass('d-none');
                    $('#' + textId).text(label);
                }
            }

            function showMsg(boxId, type, message) {
                var box = $('#' + boxId);
                box.find('.alert').removeClass('alert-danger alert-success').addClass('alert-' + type).text(message);
                box.removeClass('d-none');
            }

            function hideMsgBox(boxId) {
                $('#' + boxId).addClass('d-none');
            }
        });
    </script>
@endsection
