<?php
/**
 * Created by PhpStorm.
 * User: Igor S. Dev
 * Date: 04-Apr-18
 * Time: 13:46
 */
namespace Savvy\Payment\Setup;

use Magento\Sales\Model\Order\Status;

class UpgradeSchema implements \Magento\Framework\Setup\UpgradeSchemaInterface
{

    protected $orderStatus;

    public function __construct(
        Status $orderStatus
    )
    {
        $this->orderStatus = $orderStatus;
    }


    /**
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $setup->startSetup();


        if (version_compare($context->getVersion(), '1.0.1', '<=')) {

            $this->orderStatus->setData('status', 'mispaid')->setData('label', 'Mispaid')->save();
            $this->orderStatus->assignState(\Magento\Sales\Model\Order::STATE_CANCELED, true);

            $this->orderStatus->setData('status', 'late_payment')->setData('label', 'Late Payment')->unsetData('id')->save();
            $this->orderStatus->assignState(\Magento\Sales\Model\Order::STATE_CANCELED, true);

            $this->orderStatus->setData('status', 'awaiting_confirmations')->setData('label', 'Awaiting Confirmations')->unsetData('id')->save();
            $this->orderStatus->assignState(\Magento\Sales\Model\Order::STATE_HOLDED, true);

        }


        $setup->endSetup();
    }
}