<?php


namespace Prediction\Model;


class PredictionModelsModel extends \Gems_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('prediction-models', 'gems__prediction_models', 'gpm');
        $this->set('gpm_source_id', 'label', $this->_('Source ID'));
        $this->set('gpm_name', 'label', $this->_('Model name'));
        $this->set('gpm_id_track', 'label', $this->_('Track'));
        $this->set('gpm_url', 'label', $this->_('URL'));
    }
}