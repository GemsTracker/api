<?php

use Phinx\Migration\AbstractMigration;

class GemsAppointments extends AbstractMigration
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
        $exists = $this->hasTable('gems__appointments');
        if (!$exists) {
            $appointments = $this->table('gems__appointments', ['id' => 'gap_id_appointment', 'signed' => false]);
            $appointments
                ->addColumn('gap_id_user', 'biginteger', ['signed' => false])
                ->addColumn('gap_id_organization', 'biginteger', ['signed' => false])
                ->addColumn('gap_id_episode', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('gap_source', 'string', ['limit' => 20, 'default' => 'manual'])
                ->addColumn('gap_id_in_source', 'string', ['limit' => 40, 'null' => true])
                ->addColumn('gap_last_synch', 'timestamp', ['null' => true])
                ->addColumn('gap_manual_edit', 'integer', ['limit' => 255, 'default' => 0])
                ->addColumn('gap_code', 'string', ['limit' => 1, 'default' => 'A'])
                ->addColumn('gap_status', 'string', ['limit' => 2, 'default' => 'AC'])
                ->addColumn('gap_admission_time', 'datetime')
                ->addColumn('gap_discharge_time', 'datetime', ['null' => true])
                ->addColumn('gap_id_attended_by', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('gap_id_referred_by', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('gap_id_activity', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('gap_id_procedure', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('gap_id_location', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('gap_diagnosis_code', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('gap_subject', 'string', ['limit' => 250, 'null' => true])
                ->addColumn('gap_comment', 'text', ['null' => true])
                ->addColumn('gap_changed', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gap_changed_by', 'biginteger', ['signed' => true])
                ->addColumn('gap_created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gap_created_by', 'biginteger', ['signed' => true])
                ->create();
        }
    }
}
