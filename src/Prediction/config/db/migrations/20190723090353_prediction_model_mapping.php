<?php

use Phinx\Migration\AbstractMigration;

class PredictionModelMapping extends AbstractMigration
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
        $exists = $this->hasTable('gems__prediction_models');
        if (!$exists) {
            $predictionModels = $this->table('gems__prediction_models', ['id' => 'gpm_id', 'signed' => false]);
            $predictionModels
                ->addColumn('gpm_source_id', 'string', ['limit' => 32])
                ->addColumn('gpm_name', 'string', ['limit' => 255])
                ->addColumn('gpm_id_track', 'biginteger', ['signed' => false, 'null' => true])
                ->addColumn('gpm_url', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('gpm_changed', 'timestamp')
                ->addColumn('gpm_changed_by', 'biginteger')
                ->addColumn('gpm_created', 'timestamp', ['null' => true])
                ->addColumn('gpm_created_by', 'biginteger')
                ->create();
        }

        $exists = $this->hasTable('gems__prediction_model_mapping');
        if (!$exists) {
            $predictionModels = $this->table('gems__prediction_model_mapping',
                [
                    'id' => 'gpmm_prediction_model_id',
                    'signed' => false,
                    'primary_key' => ['gpmm_prediction_model_id', 'gpmm_name']
                ]
            );
            $predictionModels
                ->addColumn('gpmm_name', 'string', ['limit' => 100])
                ->addColumn('gpmm_required', 'boolean', ['signed' => false, 'default' => 0])
                ->addColumn('gpmm_type', 'string', ['limit' => 100])
                ->addColumn('gpmm_type_id', 'string', ['limit' => 100])
                ->addColumn('gpmm_type_sub_id', 'string', ['limit' => 100, 'null' => true])
                ->addColumn('gpmm_custom_mapping', 'text', ['null' => true])
                ->addColumn('gpmm_changed', 'timestamp')
                ->addColumn('gpmm_changed_by', 'biginteger')
                ->addColumn('gpmm_created', 'timestamp', ['null' => true])
                ->addColumn('gpmm_created_by', 'biginteger')
                ->create();
        }

    }
}
