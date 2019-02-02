<?php
namespace Savvy\Payment\Controller\Payment;


use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Savvy\Payment\Model\Payment;
use Savvy\Payment\Model\Paymenttxn;

use Magento\Framework\App\Request\Http;

class Callback extends Action
{
    protected $order;
    protected $savvyPayment;
    protected $helper;

    protected $savvyPaymenttxn;

    public function __construct(
        Context $context,
        Order $order,
        Payment $savvyPayment,
        \Savvy\Payment\Helper\Data $helper,
        Paymenttxn $savvyPaymenttxn,
        Http $request
    )
    {
        $this->order = $order;
        $this->savvyPayment = $savvyPayment;
        $this->helper = $helper;

        $this->savvyPaymenttxn = $savvyPaymenttxn;

        $this->request = $request;

        parent::__construct($context);
    }

    public function execute()
    {
        $data = file_get_contents('php://input');
        $params = $this->request->getParams();
        $order_id = (int)$params['order'];

        $response = $this->savvyPayment->checkCallback($data, $order_id);
        $this->helper->log('Order Id:' . $order_id);
        $this->helper->log($response);

        $this->getResponse()->setBody($response);
    }

}
