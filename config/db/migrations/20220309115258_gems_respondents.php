<?php

use Phinx\Migration\AbstractMigration;

class GemsRespondents extends AbstractMigration
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
        $exists = $this->hasTable('gems__respondents');
        if (!$exists) {
            $respondents = $this->table('gems__respondents', ['id' => 'grs_id_user', 'signed' => false]);
            $respondents
                ->addColumn('grs_ssn', 'string', ['limit' => 128, 'null' => true])
                ->addColumn('grs_iso_lang', 'char', ['limit' => 2, 'default' => 'en'])
                ->addColumn('grs_first_name', 'string', ['limit' => 30, 'null' => true])
                ->addColumn('grs_initials_name', 'string', ['limit' => 30, 'null' => true])
                ->addColumn('grs_surname_prefix', 'string', ['limit' => 10, 'null' => true])
                ->addColumn('grs_last_name', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('grs_raw_surname_prefix', 'string', ['limit' => 10, 'null' => true])
                ->addColumn('grs_raw_last_name', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('grs_partner_surname_prefix', 'string', ['limit' => 10, 'null' => true])
                ->addColumn('grs_partner_last_name', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('grs_last_name_order', 'string', ['limit' => 10, 'null' => true])
                ->addColumn('grs_gender', 'char', ['limit' => 1, 'default' => 'U'])
                ->addColumn('grs_dexterity', 'char', ['limit' => 1, 'default' => 'U'])
                ->addColumn('grs_birthday', 'date', ['null' => true])
                ->addColumn('grs_address_1', 'string', ['limit' => 80, 'null' => true])
                ->addColumn('grs_address_2', 'string', ['limit' => 80, 'null' => true])
                ->addColumn('grs_zipcode', 'string', ['limit' => 10, 'null' => true])
                ->addColumn('grs_city', 'string', ['limit' => 40, 'null' => true])
                ->addColumn('grs_iso_country', 'char', ['limit' => 2, 'default' => 'NL'])
                ->addColumn('grs_phone_1', 'string', ['limit' => 25, 'null' => true])
                ->addColumn('grs_phone_2', 'string', ['limit' => 25, 'null' => true])
                ->addColumn('grs_phone_3', 'string', ['limit' => 25, 'null' => true])
                ->addColumn('grs_changed', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('grs_changed_by', 'biginteger', ['signed' => true])
                ->addColumn('grs_created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('grs_created_by', 'biginteger', ['signed' => true])
                ->create();
        }
    }
}
