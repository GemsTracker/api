<?php

namespace PulseTest\Rest\Api\Emma\Fhir\Model;


class Patient extends \Gems_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('respondents', 'gems__respondents', 'grs');
        $this->addTable('gems__respondent2org', ['grs_id_user' => 'gr2o_id_user'], 'gr2o');
        $this->setKeys($this->_getKeysFor('gems__respondent2org'));

        \Gems_Model::setChangeFieldsByPrefix($this, 'grs', 1);
        \Gems_Model::setChangeFieldsByPrefix($this, 'gr2o', 1);

        $this->setOnSave('gr2o_opened', new \MUtil_Db_Expr_CurrentTimestamp());
        $this->setSaveOnChange('gr2o_opened');
        $this->setOnSave('gr2o_opened_by', 1);
        $this->setSaveOnChange('gr2o_opened_by');
    }

    public function afterRegistry()
    {
        $this->add
    }


}
