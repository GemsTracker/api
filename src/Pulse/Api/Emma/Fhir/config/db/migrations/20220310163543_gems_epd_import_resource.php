<?php

use Phinx\Migration\AbstractMigration;

class GemsEpdImportResource extends AbstractMigration
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
        $exists = $this->hasTable('gems__epd_import_resource');
        if (!$exists) {
            $importResource = $this->table('gems__epd_import_resource', ['id' => 'geir_id_import_resource', 'signed' => false]);
            $importResource
                ->addColumn('geir_source', 'string', ['limit' => 32])
                ->addColumn('geir_type', 'string', ['limit' => 32])
                ->addColumn('geir_id_user', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('geir_id_organization', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('geir_status', 'string', ['limit' => 32])
                ->addColumn('geir_errors', 'text', ['null' => true])
                ->addColumn('geir_duration', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('geir_changed', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('geir_changed_by', 'biginteger', ['signed' => true])
                ->addColumn('geir_created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('geir_created_by', 'biginteger', ['signed' => true])
                ->create();
        }
    }
}
