<?php

use Phinx\Migration\AbstractMigration;

class GemsMedicalConditions extends AbstractMigration
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
        $exists = $this->hasTable('gems__medical_conditions');
        if (!$exists) {
            $conditions = $this->table('gems__medical_conditions', ['id' => 'gmco_id_condition', 'signed' => false]);
            $conditions
                ->addColumn('gmco_source', 'string', ['limit' => 20, 'default' => 'manual'])
                ->addColumn('gmco_id_source', 'string', ['limit' => 100])
                ->addColumn('gmco_id_user', 'biginteger', ['signed' => false])
                ->addColumn('gmco_id_episode_of_care', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('gmco_status', 'string', ['limit' => 32])
                ->addColumn('gmco_code', 'string', ['limit' => 32, 'null' => true])
                ->addColumn('gmco_name', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('gmco_onset_date', 'date', ['null' => true])
                ->addColumn('gmco_abatement_date', 'date', ['null' => true])
                ->addColumn('gmco_changed', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gmco_changed_by', 'biginteger', ['signed' => true])
                ->addColumn('gmco_created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gmco_created_by', 'biginteger', ['signed' => true])
                ->create();
        }
    }
}
