<?php


namespace Prediction\Model;


class PredictionModelsModel extends \Gems_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('prediction-models', 'gems__prediction_models', 'gpm');
    }
}