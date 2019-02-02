<?php
/**
 * Created by PhpStorm.
 * User: Igor S. Dev
 * Date: 06-Apr-18
 * Time: 16:10
 */
namespace Savvy\Payment\Model\ResourceModel\Paymenttxn;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'id';


    protected function _construct()
    {
        $this->_init('Savvy\Payment\Model\Paymenttxn', 'Savvy\Payment\Model\ResourceModel\Paymenttxn');
    }

}