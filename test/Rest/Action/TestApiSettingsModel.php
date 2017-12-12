<?php


namespace GemsTest\Rest\Action;

class TestApiSettingsModel extends \MUtil_Model_TableModel
{
    public function applyApiSettings()
    {
        $this->set('message', 'apiName', 'apiSetting');


    }
}