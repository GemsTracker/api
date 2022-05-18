<?php

use Phinx\Migration\AbstractMigration;

class GemsImportEscrow extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $exists = $this->hasTable('gems__import_escrow_links');
        if (!$exists) {
            $appointments = $this->table('gems__import_escrow_links', ['id' => 'gie_id_link', 'signed' => false]);
            $appointments
                ->addColumn('gie_source', 'string', ['limit' => 32])
                ->addColumn('gie_target_resource_type', 'string', ['limit' => 32])
                ->addColumn('gie_target_id', 'string', ['limit' => 64])
                ->addColumn('gie_source_resource_type', 'string', ['limit' => 32])
                ->addColumn('gie_source_id', 'string', ['limit' => 64])
                ->addColumn('gie_changed', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gie_changed_by', 'biginteger', ['signed' => true])
                ->addColumn('gie_created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gie_created_by', 'biginteger', ['signed' => true])
                ->addIndex('gie_source')
                ->addIndex('gie_target_resource_type')
                ->addIndex('gie_target_id')
                ->create();
        }
    }
}
