define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'savvy',
                component: 'Savvy_Payment/js/view/payment/method-renderer/savvy-method'
            }
        );
        return Component.extend({});
    }
);