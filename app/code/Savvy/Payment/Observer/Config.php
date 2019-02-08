<?php
namespace Savvy\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Psr\Log\LoggerInterface as Logger;
use Savvy\Payment\Model\Payment;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Config\Storage\Writer as ConfigWriter;

class Config implements ObserverInterface
{
    protected $savvyPayment;

    protected $_messageManager;


    protected $configWriter;


    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger,
        Payment $savvyPayment,
        ConfigWriter $configWriter,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        ConfigWriter $scopeConfig
    )
    {
        $this->logger = $logger;
        $this->savvyPayment = $savvyPayment;

        $this->_messageManager = $messageManager;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(EventObserver $observer)
    {

        $response = $this->savvyPayment->checksavvyResponse();
        $message = '';

        if (empty($response)) {
            $message = "Unable to connect to Savvy. Please check your network or contact support.";
        }

        if ($response['success'] === false) {
            $message = '<b> Savvy Payment: </b> Your API Key does not seem to be correct. Get your key at <a href="https://www.savvy.io/" target="_blank"><b>savvy.io</b></a>';
        }

        if ($response['success'] === true && empty($response['data'])) {
            $message = '<b> Savvy Payment: </b> You do not have any currencies enabled, please enable them to your Merchant Dashboard: <a href="https://www.savvy.io/" target="_blank"><b>savvy.io</b></a>';
        }

        if ($message) {

            $this->configWriter->save('payment/savvy/active', '0');
            $this->_messageManager->addError($message);
        }

    }
}
