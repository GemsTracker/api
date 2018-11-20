<?php


namespace Prediction\Model;


use MUtil\Model\Type\JsonData;

class PredictionModelsMappingModel extends \Gems_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('prediction-models-mapping', 'gems__prediction_model_mapping', 'gpmm');

        $jsonType = new JsonData(10);
        $jsonType->apply($this, 'gpmm_custom_mapping', true);
    }
}