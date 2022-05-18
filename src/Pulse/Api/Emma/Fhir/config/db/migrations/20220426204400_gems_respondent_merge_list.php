<?php

use Phinx\Migration\AbstractMigration;

class GemsRespondentMergeList extends AbstractMigration
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
        $exists = $this->hasTable('gems__respondent_merge_list');
        if (!$exists) {
            $importResource = $this->table('gems__respondent_merge_list', ['id' => 'grml_id', 'signed' => false]);
            $importResource
                ->addColumn('grml_old_patient_nr', 'string', ['limit' => 32])
                ->addColumn('grml_new_patient_nr', 'string', ['limit' => 32])
                ->addColumn('grml_status', 'string', ['limit' => 32])
                ->addColumn('grml_epd', 'string', ['limit' => 32])

                //->addColumn('grml_id_organization', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('grml_info', 'text', ['null' => true])
                ->addColumn('grml_changed', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('grml_changed_by', 'biginteger', ['signed' => true])
                ->addColumn('grml_created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('grml_created_by', 'biginteger', ['signed' => true])
                ->create();
        }
    }
}
