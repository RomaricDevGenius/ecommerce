<script src="{{ static_asset('assets/js/vendors.js') }}"></script>
<script>
    (function ($) {
        // USE STRICT
        "use strict";

        AIZ.data = {
            csrf: $('meta[name="csrf-token"]').attr("content"),
            appUrl: $('meta[name="app-url"]').attr("content"),
            fileBaseUrl: $('meta[name="file-base-url"]').attr("content"),
        };
        AIZ.plugins = {
            notify: function (type = "dark", message = "") {
                $.notify(
                    {
                        // options
                        message: message,
                    },
                    {
                        // settings
                        showProgressbar: true,
                        delay: 2500,
                        mouse_over: "pause",
                        placement: {
                            from: "bottom",
                            align: "left",
                        },
                        animate: {
                            enter: "animated fadeInUp",
                            exit: "animated fadeOutDown",
                        },
                        type: type,
                        template:
                            '<div data-notify="container" class="aiz-notify alert alert-{0}" role="alert">' +
                            '<button type="button" aria-hidden="true" data-notify="dismiss" class="close"><i class="las la-times"></i></button>' +
                            '<span data-notify="message">{2}</span>' +
                            '<div class="progress" data-notify="progressbar">' +
                            '<div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>' +
                            "</div>" +
                            "</div>",
                    }
                );
            }
        };

    })(jQuery);
</script>
<script>
    @foreach (session('flash_notification', collect())->toArray() as $message)
        AIZ.plugins.notify('{{ $message['level'] }}', '{{ $message['message'] }}');
    @endforeach

    $('.password-toggle').click(function(){
        var $this = $(this);
        if ($this.siblings('input').attr('type') == 'password') {
            $this.siblings('input').attr('type', 'text');
            $this.removeClass('la-eye').addClass('la-eye-slash');
        } else {
            $this.siblings('input').attr('type', 'password');
            $this.removeClass('la-eye-slash').addClass('la-eye');
        }
    });
</script>

@if (addon_is_activated('otp_system'))
    <script type="text/javascript">
        // Country Code
        // Déterminer dynamiquement ce qui est affiché par défaut :
        // - si le bloc téléphone n'a pas la classe d-none => téléphone visible
        // - sinon => email visible
        var isPhoneShown = !$('.phone-form-group').hasClass('d-none'),
            countryData = window.intlTelInputGlobals.getCountryData(),
            input = document.querySelector("#phone-code");

        for (var i = 0; i < countryData.length; i++) {
            var country = countryData[i];
            if (country.iso2 == 'bd') {
                country.dialCode = '88';
            }
        }

        var iti = intlTelInput(input, {
            separateDialCode: true,
            utilsScript: "{{ static_asset('assets/js/intlTelutils.js') }}?1590403638580",
            onlyCountries: @php echo get_active_countries()->pluck('code') @endphp,
            customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
                if (selectedCountryData.iso2 == 'bd') {
                    return "01xxxxxxxxx";
                }
                return selectedCountryPlaceholder;
            }
        });

        var country = iti.getSelectedCountryData();
        $('input[name=country_code]').val(country.dialCode);

        input.addEventListener("countrychange", function(e) {
            // var currentMask = e.currentTarget.placeholder;
            var country = iti.getSelectedCountryData();
            $('input[name=country_code]').val(country.dialCode);

        });

        function toggleEmailPhone(el) {
            if (isPhoneShown) {
                // Téléphone actuellement visible -> passer à l'email
                $('.phone-form-group').addClass('d-none');
                $('.email-form-group').removeClass('d-none');
                $('input[name=phone]').val(null);
                isPhoneShown = false;
                $(el).html('<i>*{{ translate('Use Phone Number Instead') }}</i>');

                $('.toggle-login-with-otp').addClass('d-none');

            } else {
                // Email actuellement visible -> passer au téléphone
                $('.phone-form-group').removeClass('d-none');
                $('.email-form-group').addClass('d-none');
                $('input[name=email]').val(null);
                isPhoneShown = true;
                $(el).html('<i>*{{ translate('Use Email Instead') }}</i>');

                $('.toggle-login-with-otp').removeClass('d-none');
            }
            
            $('.submit-button').html('{{ translate('Login') }}');
            $('.password-login-block').removeClass('d-none');
            
            var url = '{{ route('login') }}';
            $('.loginForm').attr('action', url);
        }

        function toggleLoginPassOTP() {
            $('.password-login-block').addClass('d-none');
            $('.submit-button').html('{{ translate('Login With OTP') }}');

            var url = '{{ route('send-otp') }}';
            $('.loginForm').attr('action', url);
        }
    </script> 
@endif

<script>
    function showError(input, message) {
        const formGroup = input.closest('.form-group');
        $(formGroup).find('.invalid-feedback').remove(); 
        $(input).removeClass('is-valid').addClass('is-invalid');
        $(formGroup).append(`<div class="invalid-feedback d-block text-left">${message}</div>`);
    }


    document.addEventListener("input", function(e) {
        const input = e.target;
        if (input.hasAttribute("phone-number")) {
            const formGroup = input.closest('.form-group');
            const original = input.value;
            const numeric = original.replace(/[^0-9]/g, "");

            // Update input
            input.value = numeric;
            // Remove old errors
            $(formGroup).find('.invalid-feedback').remove();
            $(input).removeClass('is-invalid');

            // Show error if original != numeric
            if (original !== numeric) {
                $(input).addClass('is-invalid');
                $(formGroup).append(`<div class="invalid-feedback d-block text-left">
                    {{ translate('Please enter a valid phone number format') }}
                </div>`);
            }
        }
    });

</script>

<script>
    // Spinner de chargement sur le bouton de soumission des pages d'authentification.
    // Indique a l'utilisateur que sa demande est en cours de traitement et evite les double-clics
    // (les formulaires d'auth sont des POST classiques : sans retour visuel, l'utilisateur reclique).
    (function ($) {
        "use strict";
        $(document).on('submit', 'form', function () {
            var $btn = $(this).find('button[type="submit"]').first();
            // Rien a faire si pas de bouton submit, ou si une soumission est deja en cours.
            if (!$btn.length || $btn.data('aizSubmitting')) {
                return;
            }
            $btn.data('aizSubmitting', true);
            $btn.data('aizOriginalHtml', $btn.html());
            $btn.prop('disabled', true);
            $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

            // Filet de securite : si l'envoi est annule cote client (validation), on restaure le bouton
            // au lieu de le laisser bloque. En cas d'envoi normal la page change avant ce delai.
            setTimeout(function () {
                if ($btn.data('aizSubmitting')) {
                    $btn.html($btn.data('aizOriginalHtml'));
                    $btn.prop('disabled', false);
                    $btn.removeData('aizSubmitting');
                }
            }, 15000);
        });
    })(jQuery);
</script>