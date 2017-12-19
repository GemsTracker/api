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
        $model->set('testDateTime', 'type', \MUtil_Model::TYPE_DATETIME);
        $model->set('testDate', 'type', \MUtil_Model::TYPE_DATE);
        $model->set('testTime', 'type', \MUtil_Model::TYPE_TIME);
        $model->set('testChildModel', 'type', \MUtil_Model::TYPE_CHILD_MODEL);
        $model->set('testNoValue', 'type', \MUtil_Model::TYPE_NOVALUE);
        $model->set('testNoValidType' , 'type', 99999, 'apiName', 'testNotCorrectType');
        $model->set('by', 'validator', 'Int');
        $model->set('changed_by', 'validators', ['Int', 'Digits']);

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