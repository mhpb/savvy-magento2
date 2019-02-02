<?php
namespace Savvy\Payment\Controller\Payment;


use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Savvy\Payment\Model\Payment;
use Savvy\Payment\Model\Paymenttxn;
use Savvy\Payment\Model\Address;

use Magento\Sales\Model\Order\Status;


class Test extends Action
{
    protected $order;
    protected $savvyPayment;
    protected $savvyPaymenttxn;
    protected $address;
    protected $helper;

    protected $_orderRepository;

    protected $orderStatus;

    public function __construct(
        Context $context,
        Order $order,
        Payment $savvyPayment,
        Paymenttxn $savvyPaymenttxn,
        Address $address,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Savvy\Payment\Helper\Data $helper,
        Status $orderStatus


    )
    {
        $this->order = $order;
        $this->savvyPayment = $savvyPayment;

        $this->savvyPaymenttxn = $savvyPaymenttxn;
        $this->address = $address;

        $this->_orderRepository = $orderRepository;

        $this->helper = $helper;

        $this->orderStatus = $orderStatus;

        parent::__construct($context);
    }

    public function execute()
    {

    }

}
