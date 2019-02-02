<?php
/**
 * Created by PhpStorm.
 * User: Igor S. Dev
 * Date: 28-Mar-18
 * Time: 17:28
 */

namespace Savvy\Payment\Block\Form;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;


class Savvy extends \Magento\Framework\View\Element\Template
{
    protected $_template = 'form.phtml';
    protected $_checkoutSession;
    protected $_orderRepository;
    protected $_savvyPayment;
    protected $url;

    protected $helper;

    protected $_logger;

    protected $registry;
    protected $_order;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        Session $checkoutSession,
        \Savvy\Payment\Model\Payment $savvyPayment,
        \Savvy\Payment\Helper\Data $helper,
        \Magento\Framework\UrlInterface $url,
        Order $order,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        array $data = []
    )
    {

        $this->_checkoutSession = $checkoutSession;
        $this->_orderRepository = $orderRepository;
        $this->_savvyPayment = $savvyPayment;
        $this->helper = $helper;
        $this->url = $url;

        $this->_logger = $logger;
        $this->_order = $order;
        $this->registry = $registry;

        parent::__construct($context, $data);
    }

    public function getOrder()
    {

        $id = $this->registry->registry('last_real_order_id');
        if ($id) {
            $order = $this->_order->loadByIncrementId($id);
            if ($order->getEntityId())
                return $order;
        }

        return null;

    }

    public function getInitialData()
    {

        $order = $this->getOrder();

        if (empty($order)) {
            $this->helper->log('Order not found');
            return '';
        }

        $orderGrandTotal = (float)$order->getGrandTotal();
        $order_currency_code = strtolower($order->getOrderCurrencyCode());
        $total_paid_data = $this->_savvyPayment->getAlreadyPaid($order->getId(), $order_currency_code, $orderGrandTotal);

        $fiat_value = $orderGrandTotal;
        $currencies_url = $this->url->getUrl('savvy/payment/currencies', [
            'order' => $order->getId(),
            'order_currency_code' => $order_currency_code
        ]);

        $total_paid = 0;

        if (!empty($total_paid_data)) {
            $total_paid = $total_paid_data['total_paid'];
            $token = $total_paid_data['token'];

            if ($total_paid > 0) {
                $fiat_value = $this->getFiatValue($orderGrandTotal, $total_paid);
                $currencies_url = $this->url->getUrl('savvy/payment/currencies', [
                    'order' => $order->getId(),
                    'order_currency_code' => $order_currency_code,
                    'token' => $token
                ]);
            }
        }

        //$currencies = $this->_savvyPayment->getCurrenciesJson((float)$order->getGrandTotal(), $order->getId(), $order_currency_code, $token);
        $data['fiat_value'] = $fiat_value;
        $data['currencies'] = $currencies_url;
        //$data['currencies'] = $currencies;

        $data['status_url'] = $this->url->getUrl('savvy/payment/status', [
            'order' => $order->getId()
        ]);
        $data['redirect_url'] = $this->url->getUrl('checkout/onepage/success');
        $data['currency_iso'] = strtolower($order->getOrderCurrencyCode());
        $data['currency_sign'] = $this->helper->getCurrentCurrencySymbol();
        $data['min_overpayment_fiat'] = $this->helper->getMinoverpaymentfiat();
        $data['max_underpayment_fiat'] = $this->helper->getMaxunderpaymentfiat();
        $data['timer'] = $this->helper->getExchangeLocktime() * 60;

        $data['order_review'] = $this->getOrderInfo($order, $this->helper->getCurrentCurrencySymbol(), $fiat_value, $total_paid);

        return $data;
    }

    public function getFiatValue($orderGrandTotal, $total_paid)
    {
        $fiat_value = round(max($orderGrandTotal - $total_paid, 0), 2);

        return $fiat_value;
    }

    public function getOrderInfo($order, $currency_sign, $fiat_value, $total_paid)
    {

        $status = $order->getStatus();
        $payment_status = 'Pending Payment';
        $underpayment_fiat = $this->helper->getMaxunderpaymentfiat();
        $button_html = '<a href="#" class="button savvy_button" id="savvy-all">Pay with Crypto</a>';
        if ($status == 'complete' || $status == 'processing') {
            $payment_status = 'Paid';
            $button_html = '<a href="' . $this->url->getUrl('checkout/onepage/success') . '" class="button savvy_button" >Continue</a>';
        }

        if (($status == 'awaiting_confirmations') && ($fiat_value < $underpayment_fiat)) {
            $payment_status = 'Waiting for Confirmations';
            $button_html = '<a href=""  class="button savvy_button" >Refresh</a>';
        } elseif ($status == 'mispaid' || ($total_paid > 0 && ($fiat_value > $underpayment_fiat))) {
            $payment_status = 'Partial Payment';
        }

        $content = '<div class="woocommerce">';
        $content .= '<h2 class="section-title section-title-normal">
                    <span class="section-title-main">Order overview </span>
                    #' . $order->getIncrementId() . '
                </h2>';
        $content .= '<div class="row">';
        $content .= '<div class="col medium-3">&nbsp;</div>';
        $content .= '<div class="col medium-6">';
        $content .= '<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
                    <tr class="woocommerce-table__line-item order_item">
                        <th>Payment status</th>
                        <td>' . $payment_status . '</td>
                    </tr>';
        if ($this->_savvyPayment->load($order->getId(), 'order_id')->getId()) {
            $token = $this->_savvyPayment->getToken();
            $content .= '<tr class="woocommerce-table__line-item order_item">
                        <th>Selected token</th>
                        <td>' . strtoupper($token) . '</td>
                    </tr>';


            $address = $this->_savvyPayment->getAddress();
            if ($address) {
                $content .= '<tr class="woocommerce-table__line-item order_item">
                            <th>Payment address</th>
                            <td><a href="' . $this->_savvyPayment->getBlockExplorerUrl($token, $address) . '" target="_blank">' . $address . '</a></td>
                        </tr>';
            }
        }

        $content .= '<tr class="woocommerce-table__line-item order_item">
                         <th>Total</th>
                         <td>' . $currency_sign . round($order->getGrandTotal(), 2) . '</td>
                     </tr>';

        if ($total_paid > 0) {
            $content .= '<tr class="woocommerce-table__line-item order_item">
                         <th>Paid</th>
                         <td>' . $currency_sign . round($total_paid, 2) . '</td>
                     </tr>';
            if (($fiat_value > 0) && ($total_paid > 0) && ($fiat_value > $underpayment_fiat)) {
                $content .= '<tr class="order_item">
                             <th>To pay</th>
                             <td>' . $currency_sign . round($fiat_value, 2) . '</td>
                         </tr>';
            }
        }
        $content .= '<tr><td>' . $button_html . '</td></tr></table>';

        return $content;
    }
}