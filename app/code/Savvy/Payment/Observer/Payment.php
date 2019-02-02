<?php
namespace Savvy\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Psr\Log\LoggerInterface as Logger;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Config\Storage\Writer as ConfigWriter;

class Payment implements ObserverInterface
{
    protected $savvyPayment;

    protected $_messageManager;


    protected $configWriter;

    protected $helper;


    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger,

        ConfigWriter $configWriter,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        ConfigWriter $scopeConfig,
        \Savvy\Payment\Helper\Data $helper,
        \Savvy\Payment\Model\Payment $savvyPayment
    )
    {
        $this->logger = $logger;


        $this->_messageManager = $messageManager;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;

        $this->helper = $helper;
        $this->savvyPayment = $savvyPayment;
    }

    public function execute(EventObserver $observer)
    {

        $event           = $observer->getEvent();
        $method          = $event->getMethodInstance();
        $result          = $event->getResult();


        if($method->getCode() == 'savvy') {
            $response = $this->savvyPayment->checksavvyResponse();
            $disable = false;

            if (empty($response)) {
                $disable = true;
                $this->helper->log('Unable to connect to Savvy. Please check your network or contact support.');
            }

            if (isset($response['data']) && (empty($response['data']))) {
                $disable = true;
                $this->helper->log('You do not have any currencies enabled, please enable them to your Merchant Dashboard');
            }

            if ($disable===true) {
                $result->setData( 'is_available', false);
            }
        }

    }
}
