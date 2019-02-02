<?php
/**
 * Created by PhpStorm.
 * User: Igor S. Dev
 * Date: 19-Apr-18
 * Time: 13:23
 */
namespace Savvy\Payment\Model;

/**
 * @method \Savvy\Payment\Model\ResourceModel\Address getResource()
 * @method \Savvy\Payment\Model\ResourceModel\Address\Collection getCollection()
 */
class Address extends \Magento\Framework\Model\AbstractModel implements \Savvy\Payment\Api\Data\AddressInterface,
    \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'savvy_payment_address';
    protected $_cacheTag = 'savvy_payment_address';
    protected $_eventPrefix = 'savvy_payment_address';

    protected function _construct()
    {
        $this->_init('Savvy\Payment\Model\ResourceModel\Address');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function get($order_id, $token)
    {

        $addressObject = $this->getCollection()->addFieldToFilter('order_id', $order_id)->addFieldToFilter('token', $token)->getFirstItem();

        if ($addressObject->getId()) {
            return $addressObject;
        }

        return null;
    }
}