<?php

namespace Gems\Rest\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\ManagingOrganizationTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskExecutionPeriodTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskFocusTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskForTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskInfoTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskOwnerTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskStatusTransformer;

class QuestionnaireTaskModel extends \Gems_Model_JoinModel
{
    public function __construct()
    {
        parent::__construct('questionairetasks', 'gems__tokens', 'gto', true);
        $this->addTable('gems__respondent2org', ['gr2o_id_user' => 'gto_id_respondent', 'gr2o_id_organization' => 'gto_id_organization']);
        $this->addTable('gems__reception_codes', ['gto_reception_code' => 'grc_id_reception_code']);
        $this->addTable('gems__surveys', ['gto_id_survey' => 'gsu_id_survey']);
        $this->addTable('gems__groups', ['gsu_id_primary_group' => 'ggp_id_group']);
        $this->addTable('gems__organizations', ['gto_id_organization' => 'gor_id_organization']);
        $this->addLeftTable('gems__staff', ['gto_by' => 'gsf_id_user']);
        $this->addLeftTable('gems__agenda_staff', ['gsf_id_user' => 'gas_id_user']);
        $this->addLeftTable('gems__respondent_relations', ['gto_id_respondent' => 'grr_id_respondent', 'gto_id_relation' => 'grr_id']);


        $this->addColumn(new \Zend_Db_Expr('\'QuestionnaireTask\''), 'resourceType');
        $this->addColumn(new \Zend_Db_Expr('\'routine\''), 'priority');
        $this->addColumn(new \Zend_Db_Expr('\'order\''), 'intent');

        $this->set('resourceType', 'label', 'resourceType');
        $this->set('gto_id_token', 'label', 'id', 'apiName', 'id');
        $this->set('status', 'label', 'status', 'apiName', 'status');
        $this->set('gto_completion_time', 'label', 'completedAt', 'apiName', 'completedAt');
        $this->set('priority', 'label', 'priority');
        $this->set('intent', 'label', 'intent');
        $this->set('owner', 'label', 'owner');
        $this->set('gto_created', 'label', 'authoredOn', 'apiName', 'authoredOn');
        $this->set('gto_changed', 'label', 'lastModified', 'apiName', 'lastModified');
        $this->set('executionPeriod', 'label', 'executionPeriod');

        $this->set('managingOrganization', 'label', 'managingOrganization');
        $this->set('info', 'label', 'info');

        $this->set('patient', 'label', 'patient');
        $this->set('for', 'label', 'for');


        $this->addTransformer(new QuestionnaireTaskStatusTransformer());
        $this->addTransformer(new QuestionnaireTaskExecutionPeriodTransformer());
        $this->addTransformer(new QuestionnaireTaskOwnerTransformer());
        $this->addTransformer(new QuestionnaireTaskForTransformer());
        $this->addTransformer(new ManagingOrganizationTransformer('gto_id_organization', true));
        $this->addTransformer(new QuestionnaireTaskInfoTransformer());
        $this->addTransformer(new QuestionnaireTaskFocusTransformer());

        // Add token URL




    }
}
