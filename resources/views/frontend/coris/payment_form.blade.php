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
                            <p class="mb-3 fs-13">
                                {{ translate('To pay with Coris Money, open your CorisMoney client app and initiate an Internet payment. You will receive a withdrawal code that you must enter below with your phone number.') }}
                            </p>

                            <form id="coris-payment-form" method="POST" action="{{ route('coris.pay') }}">
                                @csrf
                                <div class="form-group">
                                    <label class="form-label">{{ translate('Coris Money phone number') }}</label>
                                    <input type="text"
                                           class="form-control"
                                           name="phone_number"
                                           placeholder="{{ translate('Phone number linked to Coris Money account') }}"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">{{ translate('Withdrawal code (codeRetrait)') }}</label>
                                    <input type="text"
                                           class="form-control"
                                           name="withdraw_code"
                                           placeholder="{{ translate('Code provided by CorisMoney app') }}"
                                           required>
                                </div>

                                <div class="form-group text-right">
                                    <button type="submit" class="btn btn-primary fw-600 d-inline-flex align-items-center justify-content-center" id="confirm_button">
                                        <span id="confirm_button_spinner" class="spinner-border spinner-border-sm mr-2 d-none" role="status" aria-hidden="true"></span>
                                        <span id="confirm_button_text">{{ translate('Confirm Payment') }}</span>
                                    </button>
                                </div>
                            </form>

                            <div id="coris-payment-message" class="mt-3 d-none">
                                <div class="alert alert-danger mb-0"></div>
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
        $(function() {
            function showLoading() {
                $('#confirm_button').prop('disabled', true);
                $('#confirm_button_spinner').removeClass('d-none');
                $('#confirm_button_text').text("{{ translate('Processing...') }}");
            }

            function hideLoading() {
                $('#confirm_button').prop('disabled', false);
                $('#confirm_button_spinner').addClass('d-none');
                $('#confirm_button_text').text("{{ translate('Confirm Payment') }}");
            }

            const form = document.getElementById('coris-payment-form');
            const messageBox = document.getElementById('coris-payment-message');

            if (!form) return;

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(form);
                const phoneNumber = formData.get('phone_number');

                messageBox.classList.add('d-none');

                showLoading();

                $.ajax({
                    url: form.getAttribute('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        hideLoading();
                        if (response.success && response.url) {
                            Swal.fire({
                                icon: 'success',
                                title: "{{ translate('Payment successful') }}",
                                html:
                                    '<p>{{ translate('Payment method') }} : <strong>Coris Money</strong></p>' +
                                    '<p>{{ translate('Phone') }} : <strong>' + phoneNumber + '</strong></p>' +
                                    '<p>{{ translate('Transaction ID') }} : <strong>' + (response.transaction_id || 'N/A') + '</strong></p>',
                                confirmButtonText: "{{ translate('Continue') }}"
                            }).then(function() {
                                window.location.href = response.url;
                            });
                        } else {
                            messageBox.querySelector('.alert').innerText = response.message || '{{ translate('Payment failed') }}';
                            messageBox.classList.remove('d-none');
                        }
                    },
                    error: function () {
                        hideLoading();
                        messageBox.querySelector('.alert').innerText = '{{ translate('An unexpected error occurred. Please try again.') }}';
                        messageBox.classList.remove('d-none');
                    }
                });
            });
        });
    </script>
@endsection

