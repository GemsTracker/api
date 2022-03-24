<?php

use Phinx\Migration\AbstractMigration;

class GemsAgendaActivities extends AbstractMigration
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
        $exists = $this->hasTable('gems__agenda_activities');
        if (!$exists) {
            $appointments = $this->table('gems__agenda_activities', ['id' => 'gaa_id_activity', 'signed' => false]);
            $appointments
                ->addColumn('gaa_name', 'string', ['limit' => 250, 'null' => true])
                ->addColumn('gaa_id_organization', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('gaa_name_for_resp', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('gaa_match_to', 'string', ['limit' => 250, 'null' => true])
                ->addColumn('gaa_code', 'string', ['limit' => 40, 'null' => true])
                ->addColumn('gaa_active', 'integer', ['limit' => 255, 'default' => 1])
                ->addColumn('gaa_filter', 'integer', ['limit' => 255, 'default' => 0])
                ->addColumn('gaa_changed', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gaa_changed_by', 'biginteger', ['signed' => true])
                ->addColumn('gaa_created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gaa_created_by', 'biginteger', ['signed' => true])
                ->create();
        }
    }
}
