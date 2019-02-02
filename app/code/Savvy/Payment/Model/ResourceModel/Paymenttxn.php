<?php
/**
 * Created by PhpStorm.
 * User: Igor S. Dev
 * Date: 06-Apr-18
 * Time: 16:10
 */
namespace Savvy\Payment\Model\ResourceModel;

class Paymenttxn extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    protected function _construct()
    {
        $this->_init('savvy_payment_txn', 'id');
    }

}