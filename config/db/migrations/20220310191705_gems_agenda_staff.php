<?php

use Phinx\Migration\AbstractMigration;

class GemsAgendaStaff extends AbstractMigration
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
        $exists = $this->hasTable('gems__agenda_staff');
        if (!$exists) {
            $appointments = $this->table('gems__agenda_staff', ['id' => 'gas_id_staff', 'signed' => false]);
            $appointments
                ->addColumn('gas_name', 'string', ['limit' => 250, 'null' => true])
                ->addColumn('gas_function', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('gas_id_organization', 'biginteger', ['signed' => false])
                ->addColumn('gas_id_user', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('gas_match_to', 'string', ['limit' => 250, 'null' => true])
                ->addColumn('gas_source', 'string', ['limit' => 20, 'default' => 'manual'])
                ->addColumn('gas_id_in_source', 'string', ['limit' => 40, 'null' => true])
                ->addColumn('gas_active', 'integer', ['limit' => 255, 'default' => 1])
                ->addColumn('gas_filter', 'integer', ['limit' => 255, 'default' => 0])
                ->addColumn('gas_changed', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gas_changed_by', 'biginteger', ['signed' => true])
                ->addColumn('gas_created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gas_created_by', 'biginteger', ['signed' => true])
                ->create();
        }
    }
}
