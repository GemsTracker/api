<?php

use Phinx\Migration\AbstractMigration;

class GemsEpisodesOfCare extends AbstractMigration
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
        $exists = $this->hasTable('gems__episodes_of_care');
        if (!$exists) {
            $episodes = $this->table('gems__episodes_of_care', ['id' => 'gec_episode_of_care_id', 'signed' => false]);
            $episodes
                ->addColumn('gec_id_user', 'biginteger', ['signed' => false])
                ->addColumn('gec_id_organization', 'biginteger', ['signed' => false])
                ->addColumn('gec_source', 'string', ['limit' => 20, 'default' => 'manual'])
                ->addColumn('gec_id_in_source', 'string', ['limit' => 40, 'null' => true])
                ->addColumn('gec_manual_edit', 'integer', ['limit' => 255, 'default' => 0])
                ->addColumn('gec_status', 'string', ['limit' => 250, 'default' => 'A'])
                ->addColumn('gec_startdate', 'date')
                ->addColumn('gec_enddate', 'date', ['null' => true])
                ->addColumn('gec_id_attended_by', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('gec_subject', 'string', ['limit' => 250, 'null' => true])
                ->addColumn('gec_comment', 'text', ['null' => true])
                ->addColumn('gec_diagnosis', 'string', ['limit' => 250, 'null' => true])
                ->addColumn('gec_diagnosis_data', 'text', ['null' => true])
                ->addColumn('gec_extra_data', 'text', ['null' => true])
                ->addColumn('gec_changed', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gec_changed_by', 'biginteger', ['signed' => true])
                ->addColumn('gec_created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gec_created_by', 'biginteger', ['signed' => true])
                ->create();
        }

    }
}
