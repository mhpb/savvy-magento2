<?php
/**
 * Created by PhpStorm.
 * User: Igor S. Dev
 * Date: 19-Apr-18
 * Time: 13:23
 */
namespace Savvy\Payment\Model\ResourceModel\Address;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'id';


    protected function _construct()
    {
        $this->_init('Savvy\Payment\Model\Address', 'Savvy\Payment\Model\ResourceModel\Address');
    }

}