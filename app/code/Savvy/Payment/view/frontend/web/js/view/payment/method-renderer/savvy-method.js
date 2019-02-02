define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url'
    ],
    function ($, Component, additionalValidators, url) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Savvy_Payment/payment/savvy'
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },

            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                        function () {
                            self.afterPlaceOrder();
                        }
                    );

                    return true;
                }

                return false;
            },


            afterPlaceOrder: function (quoteId) {


                window.location.replace(url.build('savvy/payment/payment'));


            },

            getCurrencyIcons: function (nums) {
                var request = $.ajax({
                    url: url.build('savvy/payment/icons'),
                    type: 'POST',
                    dataType: 'json',
                    data: {nums: nums}
                });

                var t = request.done(function (response) {


                    $('#cur_icons').html(response.icons);

                });

            }

        });
    }
);
