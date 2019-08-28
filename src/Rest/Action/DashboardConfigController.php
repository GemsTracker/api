<?php


namespace Gems\Rest\Action;


class DashboardConfigController extends ModelRestController
{
    public function createModel()
    {
        $model = new \Gems_Model_JoinModel('chartconfig', 'gems__chart_config', 'gcc');

        $model->setOnLoad('gcc_config', [$this, 'loadJson']);

        $model->setOnSave('gcc_config', [$this, 'saveJson']);
        \Gems_Model::setChangeFieldsByPrefix($model, 'gcc');


        /*$model->set('gcc_tid', 'label', $this->_('Track'));
        $model->set('gcc_rid', 'label', $this->_('Round'));
        $model->set('gcc_sid', 'label', $this->_('Survey'));
        $model->set('gcc_code', 'label', $this->_('Survey code'));
        $model->set('gcc_config', 'label', $this->_('Config'));
        $model->set('gcc_description', 'label', $this->_('Description'));*/

        return $model;
    }

    public function loadJson($json)
    {
        return json_decode($json, true);
    }

    public function saveJson($data)
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}