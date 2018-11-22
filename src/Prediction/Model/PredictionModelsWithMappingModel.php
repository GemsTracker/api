<?php


namespace Prediction\Model;


use MUtil\Model\Type\JsonData;
use Prediction\Model\Transform\NestedTransformerWithFilter;

class PredictionModelsWithMappingModel extends \Gems_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('prediction-models', 'gems__prediction_models', 'gpm');

        \Gems_Model::setChangeFieldsByPrefix($this, 'gpm');
        //$this->addTable('gems__prediction_model_mapping', ['gpm_id' => 'gpmm_prediction_model_id']);
        $this->set('gpm_source_id', 'label', $this->_('Source ID'));
        $this->set('gpm_name', 'label', $this->_('Model name'));
        $this->set('gpm_id_track', 'label', $this->_('Track'));
        $this->set('gpm_url', 'label', $this->_('URL'));

        $subModel = new \MUtil_Model_TableModel('gems__prediction_model_mapping', 'mappings');
        \Gems_Model::setChangeFieldsByPrefix($subModel, 'gpmm');

        $jsonType = new JsonData(10);
        $jsonType->apply($subModel, 'gpmm_custom_mapping', true);

        $this->addModel($subModel, ['gpm_id' => 'gpmm_prediction_model_id']);

    }

    /**
     * Add a 'submodel' field to the model.
     *
     * You get a nested join where a set of rows is placed in the $name field
     * of each row of the parent model.
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $joins The join fields for the sub model
     * @param string $name Optional 'field' name, otherwise model name is used
     * @return \MUtil_Model_Transform_NestedTransformer The added transformer
     */
    public function addModel(\MUtil_Model_ModelAbstract $model, array $joins, $name = null)
    {
        if (null === $name) {
            $name = $model->getName();
        }

        $trans = new NestedTransformerWithFilter();
        $trans->addModel($model, $joins);

        $this->addTransformer($trans);
        $this->set($name,
            'model', $model,
            'elementClass', 'FormTable',
            'type', \MUtil_Model::TYPE_CHILD_MODEL
        );

        return $trans;
    }
}