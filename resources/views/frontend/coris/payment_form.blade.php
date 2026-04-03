@extends('frontend.layouts.app')

@section('content')
    <section class="py-4 gry-bg">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <div class="card">
                        <div class="card-header text-center">
                            <h3 class="h6 mb-0">{{ translate('Pay by') }} Coris Money</h3>
                        </div>
                        <div class="card-body">

                            {{-- ÉTAPE 1 : Saisie du numéro de téléphone --}}
                            <div id="coris-step-1">
                                <p class="mb-3 fs-13">
                                    {{ translate('Enter your Coris Money phone number to receive your OTP code.') }}
                                </p>

                                <div class="form-group">
                                    <label class="form-label">{{ translate('Coris Money phone number') }}</label>
                                    <input type="text"
                                           class="form-control"
                                           id="coris_phone_number"
                                           placeholder="{{ translate('Phone number linked to your Coris Money account') }}"
                                           required>
                                </div>

                                <div class="form-group text-right">
                                    <button type="button"
                                            class="btn btn-primary fw-600 d-inline-flex align-items-center justify-content-center"
                                            id="btn_send_otp">
                                        <span id="spinner_send_otp" class="spinner-border spinner-border-sm mr-2 d-none" role="status" aria-hidden="true"></span>
                                        <span id="text_send_otp">{{ translate('Receive OTP code') }}</span>
                                    </button>
                                </div>

                                <div id="msg_step1" class="mt-3 d-none">
                                    <div class="alert mb-0"></div>
                                </div>
                            </div>

                            {{-- ÉTAPE 2 : Saisie du code OTP (masquée au départ) --}}
                            <div id="coris-step-2" class="d-none">
                                <div class="alert alert-success mb-3 fs-13">
                                    {{ translate('OTP code sent to your phone. Please enter it below.') }}
                                </div>

                                <form id="coris-payment-form" method="POST" action="{{ route('coris.pay') }}">
                                    @csrf
                                    <input type="hidden" id="form_phone_number" name="phone_number">

                                    <div class="form-group">
                                        <label class="form-label">{{ translate('OTP code received by SMS') }}</label>
                                        <input type="text"
                                               class="form-control"
                                               name="otp_code"
                                               id="coris_otp_code"
                                               placeholder="{{ translate('Enter the OTP code') }}"
                                               required>
                                    </div>

                                    <div class="form-group d-flex justify-content-between align-items-center">
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm"
                                                id="btn_back_step1">
                                            {{ translate('Change phone number') }}
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
@endsection

@section('script')
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript">
        $(function () {

            // --- ÉTAPE 1 : Envoi OTP ---
            $('#btn_send_otp').on('click', function () {
                var phone = $('#coris_phone_number').val().trim();
                if (!phone) {
                    showMsg('msg_step1', 'danger', '{{ translate('Please enter your phone number.') }}');
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
                            // Passer à l'étape 2
                            $('#form_phone_number').val(phone);
                            $('#coris-step-1').addClass('d-none');
                            $('#coris-step-2').removeClass('d-none');
                        } else {
                            showMsg('msg_step1', 'danger', response.message || '{{ translate('Failed to send OTP. Please try again.') }}');
                        }
                    },
                    error: function () {
                        setLoading('btn_send_otp', 'spinner_send_otp', 'text_send_otp', false, '{{ translate('Receive OTP code') }}');
                        showMsg('msg_step1', 'danger', '{{ translate('An unexpected error occurred. Please try again.') }}');
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
                    showMsg('msg_step2', 'danger', '{{ translate('Please enter the OTP code.') }}');
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
                                title: '{{ translate('Payment successful') }}',
                                html:
                                    '<p>{{ translate('Payment method') }} : <strong>Coris Money</strong></p>' +
                                    '<p>{{ translate('Phone') }} : <strong>' + phone + '</strong></p>' +
                                    '<p>{{ translate('Transaction ID') }} : <strong>' + (response.transaction_id || 'N/A') + '</strong></p>',
                                confirmButtonText: '{{ translate('Continue') }}'
                            }).then(function () {
                                window.location.href = response.url;
                            });
                        } else {
                            showMsg('msg_step2', 'danger', response.message || '{{ translate('Payment failed. Please try again.') }}');
                        }
                    },
                    error: function () {
                        setLoading('btn_confirm_payment', 'spinner_confirm', 'text_confirm', false, '{{ translate('Confirm Payment') }}');
                        showMsg('msg_step2', 'danger', '{{ translate('An unexpected error occurred. Please try again.') }}');
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
