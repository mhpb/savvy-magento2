<?php
namespace Savvy\Payment\Controller\Payment;


use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Savvy\Payment\Model\Payment\Savvy;

class Payment extends Action
{
    protected $orderFactory;
    protected $checkoutSession;
    protected $registry;

    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        Session $checkoutSession,
        Savvy $savvyPayment,
        \Magento\Framework\Registry $registry
    )
    {
        $this->orderFactory = $orderFactory;
        $this->savvyPayment = $savvyPayment;
        $this->checkoutSession = $checkoutSession;
        $this->registry = $registry;

        parent::__construct($context);
    }

    public function execute()
    {
        $last_real_order_id = $this->checkoutSession->getLastRealOrderId();
        $this->registry->register('last_real_order_id', $last_real_order_id);

        return $this->resultFactory->create('page');
    }
}
