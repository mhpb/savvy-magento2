<?php
/**
 * Created by PhpStorm.
 * User: Igor S. Dev
 * Date: 06-Apr-18
 * Time: 16:10
 */
namespace Savvy\Payment\Model;

/**
 * @method \Savvy\Payment\Model\ResourceModel\Paymenttxn getResource()
 * @method \Savvy\Payment\Model\ResourceModel\Paymenttxn\Collection getCollection()
 */
class Paymenttxn extends \Magento\Framework\Model\AbstractModel implements \Savvy\Payment\Api\Data\PaymenttxnInterface,
    \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'savvy_payment_paymenttxn';
    protected $_cacheTag = 'savvy_payment_paymenttxn';
    protected $_eventPrefix = 'savvy_payment_paymenttxn';

    protected function _construct()
    {
        $this->_init('Savvy\Payment\Model\ResourceModel\Paymenttxn');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getTxnConfirmations($order_id)
    {

        $txns = $this->getCollection()
            ->addFieldToFilter('order_id', $order_id);
        $confirmations = array();
        if ($txns->getSize() > 0)
            foreach ($txns as $txn) {
                $confirmations[] = $txn->getConfirmation();
            }

        return (count($confirmations)) ? min($confirmations) : null;
    }

    public function getTotalConfirmed($order_id, $maxConfirmations)
    {

        $txns = $this->getCollection()
            ->addFieldToFilter('order_id', $order_id);
        $totalConfirmed = 0;
        if ($txns->getSize() > 0)
            foreach ($txns as $txn) {
                if ($txn->getConfirmation() >= $maxConfirmations) {
                    $totalConfirmed += $txn->getTxnAmount();
                }
            }

        return $totalConfirmed;
    }

    public function getTotalUnconfirmed($order_id, $maxConfirmations)
    {
        $txns = $this->getCollection()
            ->addFieldToFilter('order_id', $order_id);
        $totalUnConfirmed = 0;
        if ($txns->getSize() > 0)
            foreach ($txns as $txn) {
                if ($txn->getConfirmation() < $maxConfirmations) {
                    $totalUnConfirmed += $txn->getTxnAmount();
                }
            }

        return $totalUnConfirmed;
    }

    public function getTotalPaid($order_id)
    {
        $txns = $this->getCollection()
            ->addFieldToFilter('order_id', $order_id);
        $total = 0;
        if ($txns->getSize() > 0)
            foreach ($txns as $txn) {
                $total += $txn->getTxnAmount();
            }

        return $total;
    }

    public function isNewOrder($order_id)
    {
        $txns = $this->getCollection()
            ->addFieldToFilter('order_id', $order_id);

        if ($txns->getSize() > 0) {
            return false;
        } else {
            return true;
        }
    }
}