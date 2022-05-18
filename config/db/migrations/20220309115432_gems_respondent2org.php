<?php

use Phinx\Migration\AbstractMigration;

class GemsRespondent2org extends AbstractMigration
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
        $exists = $this->hasTable('gems__respondent2org');
        if (!$exists) {
            $respondents = $this->table('gems__respondent2org', ['id' => false, 'primary_key' => ['gr2o_patient_nr', 'gr2o_id_organization']]);
            $respondents
                ->addColumn('gr2o_patient_nr', 'string', ['limit' => 20])
                ->addColumn('gr2o_id_organization', 'biginteger', ['signed' => false])
                ->addColumn('gr2o_id_user', 'biginteger', ['signed' => false])
                ->addColumn('gr2o_epd_id', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('gr2o_readonly', 'boolean', ['signed' => false, 'default' => 0])
                ->addColumn('gr2o_email', 'string', ['limit' => 100, 'null' => true])
                ->addColumn('gr2o_mailable', 'integer', ['limit' => 255, 'default' => 100])
                ->addColumn('gr2o_comments', 'text', ['null' => true])
                ->addColumn('gr2o_consent', 'string', ['limit' => 20, 'default' => 'Unknown'])
                ->addColumn('gr2o_reception_code', 'string', ['limit' => 20, 'default' => 'OK'])
                ->addColumn('gr2o_opened', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gr2o_opened_by', 'biginteger', ['signed' => true])
                ->addColumn('gr2o_changed', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gr2o_changed_by', 'biginteger', ['signed' => true])
                ->addColumn('gr2o_created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('gr2o_created_by', 'biginteger', ['signed' => true])
                ->addIndex(['gr2o_id_user', 'gr2o_id_organization'], ['unique' => true])
                ->addIndex(['gr2o_opened'])
                ->addIndex(['gr2o_reception_code'])
                ->addIndex(['gr2o_id_organization'])
                ->addIndex(['gr2o_opened_by'])
                ->addIndex(['gr2o_changed_by'])
                ->addIndex(['gr2o_consent'])
                ->create();
        }
    }
}
