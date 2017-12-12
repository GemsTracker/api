<?php


namespace GemsTest\Rest\Action;


use Gems\Rest\Action\ModelRestControllerAbstract;

class TestMessageModelRestController extends ModelRestControllerAbstract
{
    public $alias = false;

    public function createModel()
    {
        if ($this->model instanceof \MUtil_Model_ModelAbstract) {
            return $this->model;
        }

        $model = new \MUtil_Model_TableModel('test_messages', 'test');

        if ($this->alias) {
            $model->set('by', 'apiName', 'alias_by');
        }

        return $model;
    }

    public function setModel(\MUtil_Model_ModelAbstract $model)
    {
        $this->model = $model;
    }
}