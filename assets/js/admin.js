/* Admin JS — color pickers, media library, PayPal donation buttons */
(function ($) {
    'use strict';

    $(function () {
        // Color pickers (Appearance tab).
        if ($.fn.wpColorPicker) {
            $('.im-color-field').wpColorPicker();
        }

        // Media library selector for logo / background image.
        $('.im-media-btn').on('click', function (e) {
            e.preventDefault();
            var target = $($(this).data('target'));
            var frame = wp.media({
                title: (window.imAdmin && imAdmin.chooseImage) || 'Choose image',
                button: { text: (window.imAdmin && imAdmin.useImage) || 'Use this image' },
                library: { type: 'image' },
                multiple: false
            });
            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                target.val(attachment.url).trigger('change');
            });
            frame.open();
        });

        // Donations: amount selection + PayPal Smart Buttons.
        var $paypalContainer = $('#im-paypal-buttons');
        if ($paypalContainer.length && typeof paypal !== 'undefined') {
            var selectedAmount = '5';
            var currency = $paypalContainer.data('currency') || 'USD';

            $('.im-amount-btn').on('click', function () {
                $('.im-amount-btn').removeClass('active');
                $(this).addClass('active');
                selectedAmount = String($(this).data('amount'));
            });
            $('.im-amount-btn[data-amount="5"]').addClass('active');

            paypal.Buttons({
                style: { layout: 'vertical', color: 'blue', shape: 'pill', label: 'donate' },
                createOrder: function (data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: { value: selectedAmount, currency_code: currency },
                            description: 'Donation — Icontec Maintenance Mode'
                        }]
                    });
                },
                onApprove: function (data, actions) {
                    return actions.order.capture().then(function () {
                        $paypalContainer.before(
                            '<div class="notice notice-success"><p>☕ ¡Gracias! / Thank you!</p></div>'
                        );
                    });
                }
            }).render('#im-paypal-buttons');
        }
    });
})(jQuery);
