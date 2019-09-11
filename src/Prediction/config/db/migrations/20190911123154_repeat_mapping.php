<?php


use Phinx\Migration\AbstractMigration;

class RepeatMapping extends AbstractMigration
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
        $predictionModels = $this->table('gems__prediction_model_mapping',
            [
                'id' => 'gpmm_prediction_model_id',
                'signed' => false,
                'primary_key' => ['gpmm_prediction_model_id', 'gpmm_name']
            ]
        );

        $predictionModels->addColumn('gpmm_repeat', 'boolean', ['after' => 'gpmm_required', 'signed' => false, 'default' => 0])
            ->update();
    }
}
