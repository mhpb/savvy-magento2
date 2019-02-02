<?php
/**
 * Created by PhpStorm.
 * User: Igor S. Dev
 * Date: 04-Apr-18
 * Time: 13:46
 */
namespace Savvy\Payment\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     *
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();


        $tableName = 'savvy_payment';
        if (!$installer->tableExists($tableName)) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable($tableName)
            )->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity'  => true,
                        'nullable'  => false,
                        'primary'   => true,
                        'unsigned'  => true,
                    ],
                    'ID'
                )->addColumn(
                    'order_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Magento Order Id'
                )->addColumn(
                    'token',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Coin'
                )->addColumn(
                    'address',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Address'
                )->addColumn(
                    'invoice',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Savvy Invoice'
                )->addColumn(
                    'amount',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    '20,8',
                    [],
                    'Coin amount'
                )->addColumn(
                    'max_confirmation',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [],
                    'Max confirmation'
                )->addColumn(
                    'email_status',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    [],
                    'Email send'
                )->addColumn(
                    'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    [],
                    'Created At'
                )->addColumn(
                    'updated_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    [],
                    'Updated At'
                )->addColumn(
                    'paid_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    [],
                    'Paid At'
                )->addIndex(
                $installer->getIdxName($tableName, ['id']),
                ['id']
                )->addIndex(
                    $installer->getIdxName($tableName, ['order_id']),
                    ['order_id']
                );

            $installer->getConnection()->createTable($table);

            $installer->endSetup();
        }

        $tableName = 'savvy_address';
        if (!$installer->tableExists($tableName)) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable($tableName)
            )->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'identity'  => true,
                    'nullable'  => false,
                    'primary'   => true,
                    'unsigned'  => true,
                ],
                'ID'
            )->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Magento Order Id'
            )->addColumn(
                'token',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Coin'
            )->addColumn(
                'address',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Address'
            )->addColumn(
                'invoice',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Savvy Invoice'
            )->addIndex(
                $installer->getIdxName($tableName, ['id']),
                ['id']
            )->addIndex(
                $installer->getIdxName($tableName, ['order_id']),
                ['order_id']
            )->addForeignKey(
                $installer->getFkName($tableName, 'order_id', 'savvy_payment', 'order_id'),
                'order_id',
                $installer->getTable('savvy_payment'),
                'order_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );

            $installer->getConnection()->createTable($table);

            $installer->endSetup();
        }

        $tableName = 'savvy_payment_txn';
        if (!$installer->tableExists($tableName)) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable($tableName)
            )->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'identity'  => true,
                    'nullable'  => false,
                    'primary'   => true,
                    'unsigned'  => true,
                ],
                'ID'
            )->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Magento Order Id'
            )->addColumn(
                'txn_hash',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Txn Hash'
            )->addColumn(
                'invoice',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Invoice'
            )->addColumn(
                'txn_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '20,8',
                [],
                'Transaction Amount'
            )->addColumn(
                'confirmation',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Transaction confirmations'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [],
                'Created At'
            )->addColumn(
                'updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [],
                'Updated At'
            )->addIndex(
                $installer->getIdxName($tableName, ['id']),
                ['id']
            )->addIndex(
                $installer->getIdxName($tableName, ['order_id']),
                ['order_id']
            )->addForeignKey(
                $installer->getFkName($tableName, 'order_id', 'savvy_payment', 'order_id'),
                'order_id',
                $installer->getTable('savvy_payment'),
                'order_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );

            $installer->getConnection()->createTable($table);

            $installer->endSetup();
        }
    }
}