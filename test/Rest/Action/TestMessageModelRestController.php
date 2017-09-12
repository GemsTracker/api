<?php


namespace GemsTest\Rest\Action;


use Gems\Rest\Action\ModelRestControllerAbstract;

class TestMessageModelRestController extends ModelRestControllerAbstract
{
    public function createModel()
    {
        return new \MUtil_Model_TableModel('test_messages', 'test');
    }
}