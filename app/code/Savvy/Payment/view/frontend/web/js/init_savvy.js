define([
    'jquery'
], function ($j) {

    return function (savvy) {


        if (savvy.config.fiat_value > 0) {
            window.paybear = new Paybear({
                button: '#savvy-all',
                fiatValue: savvy.config.fiat_value,
                currencies: savvy.config.currencies,
                statusUrl: savvy.config.status_url,
                redirectTo: savvy.config.redirect_url,
                fiatCurrency: savvy.config.currency_iso,
                fiatSign: savvy.config.currency_sign,
                minOverpaymentFiat: parseFloat(savvy.config.min_overpayment_fiat),
                maxUnderpaymentFiat: parseFloat(savvy.config.max_underpayment_fiat),
                modal: true,
                enablePoweredBy: true,
                timer: savvy.config.timer
            });

            if (document.getElementById('savvy-all')) {
                    document.getElementById('savvy-all').click();
            }
        }


    }
});