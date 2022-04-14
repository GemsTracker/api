<?php

namespace Pulse\Api\Emma\Fhir\Model;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientAddressTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientDeceasedTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientNameTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientOtherFieldsTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientTelecomTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ResourceTypeTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\SourceIdTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ValidateFieldsTransformer;
use Pulse\Model\ModelUpdateDiffs;

class RespondentModel extends \Gems_Model_JoinModel
{
    use ModelUpdateDiffs;

    public function __construct()
    {
        parent::__construct('respondentModel', 'gems__respondents', 'grs');
        $this->addTable('gems__respondent2org', ['grs_id_user' => 'gr2o_id_user'], 'gr2o');
        $this->setKeys($this->_getKeysFor('gems__respondent2org'));

        \Gems_Model::setChangeFieldsByPrefix($this, 'grs', 1);
        \Gems_Model::setChangeFieldsByPrefix($this, 'gr2o', 1);

        $this->setOnSave('gr2o_opened', new \MUtil_Db_Expr_CurrentTimestamp());
        $this->setSaveOnChange('gr2o_opened');
        $this->setOnSave('gr2o_opened_by', 1);
        $this->setSaveOnChange('gr2o_opened_by');

        $this->addTransformer(new ResourceTypeTransformer('Patient'));
        $this->addTransformer(new SourceIdTransformer('gr2o_epd_id'));
        $this->addTransformer(new PatientNameTransformer());
        $this->addTransformer(new PatientAddressTransformer());
        $this->addTransformer(new PatientTelecomTransformer());
        $this->addTransformer(new PatientOtherFieldsTransformer());
        $this->addTransformer(new PatientDeceasedTransformer());
    }


}
