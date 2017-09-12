<?php


namespace GemsTest\Rest\Action;


use Gems\Rest\Action\ModelRestControllerAbstract;

class EmptyModelRestController extends ModelRestControllerAbstract
{
    public function createModel()
    {
        return new \Gems_Model_PlaceholderModel('emptyModel', []);
    }
}