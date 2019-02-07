<?php
/**
 * Created by PhpStorm.
 * User: Igor S. Dev
 * Date: 06-Apr-18
 * Time: 16:02
 */
namespace Savvy\Payment\Model;


//use Magento\Framework\App\Response\Http;

use Magento\Framework\Model\Context;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;


use Savvy\Payment\Model\Paymenttxn;
use Savvy\Payment\Model\Address;

use Magento\Framework\App\Request\Http;
use Magento\Sales\Model\Order;


/**
 * @method \Savvy\Payment\Model\ResourceModel\Payment getResource()
 * @method \Savvy\Payment\Model\ResourceModel\Payment\Collection getCollection()
 */
class Payment extends \Magento\Framework\Model\AbstractModel implements \Savvy\Payment\Api\Data\PaymentInterface,
    \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'savvy_payment_payment';
    protected $_cacheTag = 'savvy_payment_payment';
    protected $_eventPrefix = 'savvy_payment_payment';

    protected $url;
    protected $storeManager;
    protected $helper;


    protected static $_currencies = null;
    protected static $_rates = null;


    protected $paymenttxn;
    protected $address;
    protected $request;

    protected $_orderRepository;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    public $curl;

    protected $apiKey;
    protected $apiKeyTestnet;
    protected $apiDomain;

    public function __construct(
        Context $context,
        Registry $registry,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $url,
        StoreManagerInterface $storeManager,
        \Savvy\Payment\Helper\Data $helper,
        Curl $curl,
        Paymenttxn $paymenttxn,
        Address $address,
        Http $request,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,

        array $data = array()
    )
    {
        parent::__construct($context, $registry);

        $this->url = $url;

        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->curl = $curl;

        $this->apiKey = $this->helper->getApiSecretKey();
        $this->apiKeyTestnet = $this->helper->getApiSecretKeyTestnet();
        $this->apiDomain = $this->helper->getApiDomain();

        $this->paymenttxn = $paymenttxn;
        $this->address = $address;

        $this->request = $request;
        $this->_orderRepository = $orderRepository;

    }

    protected function _construct()
    {
        $this->_init('Savvy\Payment\Model\ResourceModel\Payment');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    function getCurrenciesJson($order_total, $order_id, $order_currency_code, $token = 'all')
    {
        $token = $this->helper->sanitize_token($token);

        $result = array();

        foreach ($this->getCurrencies() as $code => $currency) {
            $rate = $this->getRate($code, $order_currency_code);
            if ($rate) {
                $amount = round($order_total / $rate, 8);
                $currency = (object)$currency;
                $maximum = true;
                if ($currency->maximum > 0 && $amount > $currency->maximum) {
                    $maximum = false;
                    $err_msg = sprintf('Incorrect order amount. Order ID: %s, crypto code: %s, crypto maximum: %s, order amount: %s', $order_id, $code, $currency->maximum, $amount);
                    $this->helper->log($err_msg);
                }

                if ($amount >= $currency->minimum && $maximum) {
                    if (($token == $code) || (count($this->getCurrencies()) == 1)  ) {

                        $unconfirmedTotal = $this->getAlreadyPaidCoins($order_id);

                        $address = $this->getPaymentAddress($code, $order_id, $amount, $currency->maxConfirmations);
                        $currency->coinsValue = $amount;
                        $currency->coinsPaid = $unconfirmedTotal;
                        $currency->rate = round($rate, 2);
                        $currency->address = $address;

                        $result = (array)$currency;
                        break;

                    } elseif ($token == 'all') {

                        $currency->currencyUrl = $this->url->getUrl('savvy/payment/currencies', [
                            'order' => $order_id,
                            'order_currency_code' => $order_currency_code,
                            'token' => $code
                        ]);
                        $currency->coinsValue = $amount;
                        $currency->rate = round($rate, 2);

                        $result[] = $currency;
                    }
                } else {
                    if (!($amount >= $currency->minimum)) {
                        $err_msg = sprintf('Incorrect order amount. Order ID: %s, crypto code: %s, crypto minimum: %s, order amount: %s', $order_id, $code, $currency->minimum, $amount);
                        $this->helper->log($err_msg);
                    }
                }
            }
        }

        return $this->helper->jsonEncode($result);
        //return $result;

    }


    public function getRate($curCode, $order_currency_code)
    {
        $curCode = strtolower($curCode);
        if ($curCode == $order_currency_code)
            return 1;

        /**
         * Check, is order currency crypto ?
         */
        if (in_array($order_currency_code, $this->getCryptoCurrencies())) {

            $rateCryptoToCrypto = $this->getCryptoToCrypto($curCode, $order_currency_code);

            return $rateCryptoToCrypto;

        }

        $rates = $this->getRates($order_currency_code);

        return isset($rates[$curCode]) ? $rates[$curCode]['mid'] : false;
    }

    public function getRates($order_currency_code)
    {

        if (self::$_rates === null) {
            $url = sprintf('%s/v3/exchange/%s/rate', $this->apiDomain, $order_currency_code);
            $response = $this->request($url);

            $data = json_decode($response, true);
            if (isset($data) && $data['success']) {
                self::$_rates = $data['data'];
            }

        }

        return self::$_rates;

    }


    /**
     * Get Cryptocurrency codes
     * @return array
     */
    public function getCryptoCurrencies() {
        return array_keys($this->getCurrencies());
    }

    /**
     * Get rate of one cryptocurrency to another cryptocurrency
     * @param $curCode
     * @param $order_currency_code
     * @return bool|float|int
     */
    public function getCryptoToCrypto($curCode, $order_currency_code) {

        // get USD rates
        $rates = $this->getRates('usd');

        $curCodeRate = ($rates[$curCode]) ? $rates[$curCode]['mid'] : null;
        $orderCurrencyRate = ($rates[$order_currency_code]) ? $rates[$order_currency_code]['mid'] : null;

        if (($curCodeRate > 0) && ($orderCurrencyRate > 0)) {
            return $curCodeRate/$orderCurrencyRate;
        }

        return false;
    }

    public function getPaymentAddress($token, $order_id, $amount, $maxConfirmations)
    {
        $addressObject = $this->address->get($order_id, $token);
        $payment = $this->_checkIsPaymentExist($order_id);

        $payment_data['token'] = $token;
        $payment_data['amount'] = $amount;
        $payment_data['max_confirmation'] = $maxConfirmations;

        if (empty($addressObject)) {
            $token_address_data = $this->getTokenAddressData($token, $order_id);

            if ($token_address_data->address) {

                if (empty($payment)) {

                    $payment_data['order_id'] = $order_id;
                    $payment_data['created_at'] = date('Y-m-d H:i:s');
                    $payment_data['address'] = $token_address_data->address;
                    $payment_data['invoice'] = $token_address_data->invoice;

                    $this->_setsavvyPayment($payment_data);
                } else {
                    $payment_data['address'] = $token_address_data->address;
                    $payment_data['invoice'] = $token_address_data->invoice;
                    $payment_data['updated_at'] = date('Y-m-d H:i:s');

                    $this->_updatesavvyPayment($payment_data, $payment);
                }

                $address_data = array(
                    'order_id' => $order_id,
                    'token' => $token,
                    'address' => $token_address_data->address,
                    'invoice' => $token_address_data->invoice
                );

                $this->address->setData($address_data)->save();

                $address = $token_address_data->address;
                return $address;
            }

        } else {
            $address = $addressObject->getAddress();
            $payment_data['address'] = $address;
            $payment_data['invoice'] = $addressObject->getInvoice();
            $payment_data['updated_at'] = date('Y-m-d H:i:s');

            if ($payment) {
                $this->_updatesavvyPayment($payment_data, $payment);
            }

            return $address;
        }

    }

    public function getTokenAddressData($token, $order_id)
    {
        $callbackUrl = $this->url->getUrl('savvy/payment/callback', [
            'order' => $order_id,
            'ajax' => 1
        ]);

        $lock_address_timeout = $this->helper->getLockAddressTimeout();

        $url = sprintf('%s/v3/%s/payment/%s?token=%s&lock_address_timeout=%s', $this->apiDomain, $token, urlencode($callbackUrl), $this->apiKey, $lock_address_timeout);

        if ($this->helper->getTestnet())
            $url = sprintf('%s/v3/%s/payment/%s?token=%s&lock_address_timeout=%s', $this->apiDomain, $token, urlencode($callbackUrl), $this->apiKeyTestnet, $lock_address_timeout);

        $response = $this->request($url);

        $response = json_decode($response);
        if (isset($response->data)) {
            return $response->data;
        }

        return null;
    }

    public function getCurrencies()
    {
        if (self::$_currencies === null) {
            $url = sprintf('%s/v3/currencies?token=%s', $this->apiDomain, $this->apiKey);

            if ($this->helper->getTestnet())
                $url = sprintf('%s/v3/currencies?token=%s', $this->apiDomain, $this->apiKeyTestnet);

            $response = $this->request($url);

            $data = json_decode($response, true);
            if (isset($data) && $data['success']) {
                self::$_currencies = $data['data'];
            }
        }

        return self::$_currencies;
    }

    public function request($url)
    {

        $httpHeaders = new \Zend\Http\Headers();
        $httpHeaders->addHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);

        $request = new \Zend\Http\Request();
        $request->setHeaders($httpHeaders);
        $request->setUri($url);

        $request->setMethod(\Zend\Http\Request::METHOD_GET);

        $client = new \Zend\Http\Client();
        $options = [
            'adapter' => 'Zend\Http\Client\Adapter\Curl',
            'curloptions' => [CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYHOST => false],
            'timeout' => 10
        ];
        $client->setOptions($options);

        $response = $client->send($request);

        $response = $response->getBody();

        return $response;
    }

    protected function _checkIsPaymentExist($order_id)
    {
        $payment = $this->getCollection()
            ->addFieldToFilter('order_id', $order_id)
            ->getFirstItem();

        if ($payment->getId()) {
            return $payment;
        }

        return false;
    }

    protected function _setsavvyPayment($payment_data)
    {
        $this->setData($payment_data)->save();
    }

    protected function _updatesavvyPayment($payment_data, $payment)
    {
        $payment->addData($payment_data)->save();
    }

    public function checkCallback($data, $order_id)
    {

        if ($order_id) {
            $order = $this->_orderRepository->get($order_id);

            if (!$order->getId()) {
                return 'No Order';
            }

            $payment = $this->load($order_id, 'order_id');

            if (!$payment->getId()) {
                return 'No Savvy Payment Order';
            }
        }

        //todo get status from config
        if (!in_array($order->getStatus(), array(
            'pending_payment',
            'mispaid',
            'awaiting_confirmations',
            'pending'
        ))
        ) {
            return 'Order status: ' . $order->getStatus();
        }

        if ($data) {
            $params = json_decode($data);

            $this->helper->log('Savvy response:');
            $this->helper->log($params);

            $invoice = $payment->getInvoice();
            $currency_code = strtolower($order->getOrderCurrencyCode());

            if ($params->invoice == $invoice) {

                $isNewPayment = $this->paymenttxn->isNewOrder($order_id);

                if ($isNewPayment) {
                    $payment->setPaidAt(date('Y-m-d H:i:s'));
                    $payment->save();
                }

                $this->setTxn($params, $order_id);

                $total_confirmed = $this->paymenttxn->getTotalConfirmed($order_id, $payment->getMaxConfirmation());
                $maxDifference_fiat = $this->helper->getMaxunderpaymentfiat();
                $maxDifference_coins = round($maxDifference_fiat / $this->getRate($params->blockchain, $currency_code), 8);
                $maxDifference_coins = max($maxDifference_coins, 0.00000001);

                if ($payment->getAmount() - $maxDifference_coins <= $total_confirmed) {

                    $order->setState(Order::STATE_PROCESSING);
                    $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
                    $order->save();

                    $orderTimestamp = strtotime($payment->getCreatedAt());
                    $paymentTimestamp = strtotime($payment->getPaidAt());
                    $deadline = $orderTimestamp + $this->helper->getExchangeLocktime() * 60;
                    $fiatPaid = $total_confirmed * $this->getRate($params->blockchain, $currency_code);

                    if ($paymentTimestamp > $deadline) {


                        if ((float)$fiatPaid < $order->getGrandTotal()) {

                            $message = __(
                                'Late Payment / Rate changed (%1 %2 paid, %3 %4 expected)',
                                $fiatPaid,
                                $currency_code,
                                $order->getGrandTotal(),
                                $currency_code
                            );
                            $order->addStatusHistoryComment($message)->setIsCustomerNotified(true)->save();

                            /** @var OrderCommentSender $orderCommentSender */
                            $orderCommentSender = $this->_objectManager
                                ->create(\Magento\Sales\Model\Order\Email\Sender\OrderCommentSender::class);

                            $orderCommentSender->send($order, true, $message);

                            $order->setStatus('late_payment');
                            $order->save();
                        }
                    }

                    //check overpaid
                    $minoverpaid = $this->helper->getMinoverpaymentfiat();

                    $overpaid = (round(($total_confirmed - $payment->getAmount()) * $this->getRate($params->blockchain, $currency_code), 2));
                    if (($minoverpaid > 0) && ($overpaid > $minoverpaid)) {

                        $cryptoverpaid = $total_confirmed - $payment->getAmount();
                        $message = __(
                            "Whoops, you overpaid %1 %2 ( about %3 %4)
                            Don't worry, here is what to do next:
                            To get your overpayment refunded, please contact the merchant directly and share your Order ID %5 and %6 Address to send your refund to.
                            Tips for Paying with Crypto:
                            Tip 1) When paying, ensure you send the correct amount in %7.
                            Do not manually enter the %8 Value.
                            Tip 2)  If you are sending from an exchange, be sure to correctly factor in their withdrawal fees.
                            Tip 3) Be sure to successfully send your payment before the countdown timer expires.
                            This timer is setup to lock in a fixed rate for your payment. Once it expires, rates may change.",
                            $cryptoverpaid,
                            $params->blockchain,
                            $fiatPaid,
                            $currency_code,
                            $order->getIncrementId(),
                            $params->blockchain,
                            $params->blockchain,
                            $currency_code
                        );
                        $order->addStatusHistoryComment($message)->setIsCustomerNotified(true)->save();

                        /** @var OrderCommentSender $orderCommentSender */
                        $orderCommentSender = $this->_objectManager
                            ->create(\Magento\Sales\Model\Order\Email\Sender\OrderCommentSender::class);

                        $orderCommentSender->send($order, true, $message);
                    }

                    $message = __(
                        'Amount Paid: %1, Blockchain: %2. ',
                        $total_confirmed,
                        $params->blockchain);
                    $order->addStatusHistoryComment($message)->setIsCustomerNotified(true)->save();

                    /** @var OrderCommentSender $orderCommentSender */
                    $orderCommentSender = $this->_objectManager
                        ->create(\Magento\Sales\Model\Order\Email\Sender\OrderCommentSender::class);

                    $orderCommentSender->send($order, true, $message);

                    return $params->invoice;
                } else {
                    $maxConfirmations = $payment->getMaxConfirmation();
                    $totalConfirmations = $this->paymenttxn->getTxnConfirmations($order_id);


                    if ($totalConfirmations >= $maxConfirmations) {
                        if ($total_confirmed > 0 && ($payment->getAmount() - $maxDifference_coins > $total_confirmed)) {


                            if ($order->getStatus() != 'mispaid') {

                                $order->setStatus('mispaid');
                                $order->save();

                                $underpaid = round(($payment->getAmount() - $total_confirmed) * $this->getRate($params->blockchain, $currency_code), 2);

                                $message = __(
                                    'Wrong Amount Paid (%1 %2 is received, %3 %4 is expected) - %5 %6 is underpaid',
                                    $total_confirmed,
                                    $params->blockchain,
                                    $payment->getAmount(),
                                    $params->blockchain,
                                    $currency_code,
                                    $underpaid);

                                $order->addStatusHistoryComment($message)->setIsCustomerNotified(false)->save();
                            }

                        }

                    } else {
                        if ($order->getStatus() != 'awaiting_confirmations') {

                            $order->setStatus('awaiting_confirmations');
                            $order->save();

                            $unconfirmedTotal = $this->paymenttxn->getTotalUnconfirmed($order_id, $maxConfirmations);

                            $message = __(
                                '%1 Awaiting confirmation. Total Unconfirmed: %2 %3',
                                date('Y-m-d H:i:s'),
                                $unconfirmedTotal,
                                $params->blockchain);

                            $order->addStatusHistoryComment($message)->setIsCustomerNotified(false)->save();
                        }
                    }
                }

            }

        }

        return 'No Savvy Response';

    }

    public function setTxn($params, $order_id)
    {

        $txn_hash = $params->inTransaction->hash;

        $txn_data = array(
            'order_id' => $order_id,
            'txn_hash' => $txn_hash,
            'invoice' => $params->invoice,
            'txn_amount' => $params->inTransaction->amount / pow(10, $params->inTransaction->exp),
            'confirmation' => $params->confirmations
        );

        $txn = $this->paymenttxn->load($txn_hash, 'txn_hash');

        if (!$txn->getId()) {
            $txn_data['created_at'] = date('Y-m-d H:i:s');

            $this->paymenttxn->setData($txn_data)->save();
        } else {
            $txn_data['updated_at'] = date('Y-m-d H:i:s');

            $this->paymenttxn->addData($txn_data)->save();
        }

    }

    public function getAlreadyPaid($order_id, $order_currency_code, $orderGrandTotal)
    {
        try {
            $savvy_payment = $this->load($order_id, 'order_id');
            if ($savvy_payment->getId()) {
                $token = $savvy_payment->getToken();

                $rate = round($orderGrandTotal / $savvy_payment->getAmount(), 8);
                $orderTimestamp = strtotime($savvy_payment->getPaidAt());
                $rate_lock_time = $this->helper->getExchangeLocktime();
                $deadline = $orderTimestamp + $rate_lock_time * 60;
                if (time() > $deadline) {
                    $rate = $this->getRate($token, $order_currency_code);
                }

                $already_paid = $this->paymenttxn->getTotalPaid($order_id);
                $result['total_paid'] = round($already_paid * $rate, 2);
                $result['token'] = $token;

                return $result;
            }

        } catch (\Exception $e) {

        }

        return null;
    }

    public function getAlreadyPaidCoins($order_id)
    {
        try {
            $savvy_payment = $this->load($order_id, 'order_id');
            if ($savvy_payment->getId()) {

                $already_paid = $this->paymenttxn->getTotalPaid($order_id);
                return $already_paid;
            }

        } catch (\Exception $e) {

        }

        return 0;

    }

    public function checksavvyResponse()
    {
        $url = sprintf('%s/v3/currencies?token=%s', $this->apiDomain, $this->apiKey);
        if ($this->helper->getTestnet())
            $url = sprintf('%s/v3/currencies?token=%s', $this->apiDomain, $this->apiKeyTestnet);

        $response = $this->request($url);
        $data = json_decode($response, true);

        return $data;
    }

    public function getBlockExplorerUrl($token, $address)
    {
        $currencies = $this->getCurrencies();
        foreach ($currencies as $token_code => $currency) {
            if ($token_code == $token) {
                return sprintf($currency['blockExplorer'], $address);
            }
        }
    }


}