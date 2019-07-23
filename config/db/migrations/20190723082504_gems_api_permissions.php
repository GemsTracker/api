<?php

use Phinx\Migration\AbstractMigration;

class GemsApiPermissions extends AbstractMigration
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
        $permissions = $this->table('gems__api_permissions', ['id' => 'gapr_id', 'signed' => false]);
        $permissions
            ->addColumn('gapr_role', 'string', ['limit' => 30])
            ->addColumn('gapr_resource', 'string', ['limit' => 50])
            ->addColumn('gapr_permission', 'string', ['limit' => 30])
            ->addColumn('gapr_allowed', 'boolean')
            ->create();
    }
}
