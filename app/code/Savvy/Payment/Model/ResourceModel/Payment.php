<?php
/**
 * Created by PhpStorm.
 * User: Igor S. Dev
 * Date: 06-Apr-18
 * Time: 16:02
 */
namespace Savvy\Payment\Model\ResourceModel;

class Payment extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    protected function _construct()
    {
        $this->_init('savvy_payment', 'id');
    }

}