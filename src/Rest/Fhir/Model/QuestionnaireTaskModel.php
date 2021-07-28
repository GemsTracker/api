<?php

namespace Gems\Rest\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\ManagingOrganizationTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskExecutionPeriodTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireReferenceTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskForTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskInfoTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireOwnerTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskStatusTransformer;

class QuestionnaireTaskModel extends \Gems_Model_JoinModel
{
    /**
     * @var \Gems_Util
     */
    public $util;

    public function __construct()
    {
        parent::__construct('questionairetasks', 'gems__tokens', 'gto', true);
        $this->addTable('gems__respondent2org', ['gr2o_id_user' => 'gto_id_respondent', 'gr2o_id_organization' => 'gto_id_organization']);
        $this->addTable('gems__respondent2track', ['gto_id_respondent_track' => 'gr2t_id_respondent_track']);
        $this->addTable('gems__reception_codes', ['gto_reception_code' => 'grc_id_reception_code']);
        $this->addTable('gems__surveys', ['gto_id_survey' => 'gsu_id_survey']);
        $this->addTable('gems__tracks', ['gto_id_track' => 'gtr_id_track']);
        $this->addTable('gems__groups', ['gsu_id_primary_group' => 'ggp_id_group']);
        $this->addTable('gems__organizations', ['gto_id_organization' => 'gor_id_organization']);
        $this->addLeftTable('gems__staff', ['gto_by' => 'gsf_id_user']);
        $this->addLeftTable('gems__agenda_staff', ['gsf_id_user' => 'gas_id_user']);
        $this->addLeftTable('gems__respondent_relations', ['gto_id_respondent' => 'grr_id_respondent', 'gto_id_relation' => 'grr_id']);


        $this->addColumn(new \Zend_Db_Expr('\'QuestionnaireTask\''), 'resourceType');
        $this->addColumn(new \Zend_Db_Expr('\'routine\''), 'priority');
        $this->addColumn(new \Zend_Db_Expr('\'order\''), 'intent');

        $this->addColumn('(SELECT grc_success as respondentTrackSuccess FROM gems__reception_codes WHERE grc_id_reception_code = gr2t_reception_code)', 'respondentTrackSuccess');

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
        $this->set('owner.name', 'label', 'owner.name');
        $this->set('owner_name', 'label', 'owner_name');
        $this->set('owner.type', 'label', 'owner.type');
        $this->set('owner_type', 'label', 'owner_type');
        $this->set('survey', 'label', 'survey');
        $this->set('survey_name', 'label', 'survey_name');
        $this->set('survey_code', 'label', 'survey_code');
        $this->set('questionnaire', 'label', 'questionnaire');
        $this->set('questionnaire_name', 'label', 'questionnaire_name');
        $this->set('questionnaire_code', 'label', 'questionnaire_code');

        $this->set('roundDescription', 'label', 'roundDescription');
        $this->set('track', 'label', 'track');
        $this->set('track_name', 'label', 'track_name');
        $this->set('track_code', 'label', 'track_code');
        $this->set('carePlan', 'label', 'carePlan');
        $this->set('respondentTrackId', 'label', 'respondentTrackId');

        $this->set('gto_round_order', 'label', 'roundOrder', 'apiName', 'roundOrder');



        // Add token URL
    }

    public function afterRegistry()
    {
        $currentUri = $this->util->getCurrentURI();

        $this->addTransformer(new QuestionnaireTaskStatusTransformer());
        $this->addTransformer(new QuestionnaireTaskExecutionPeriodTransformer());
        $this->addTransformer(new QuestionnaireOwnerTransformer());
        $this->addTransformer(new QuestionnaireTaskForTransformer());
        $this->addTransformer(new ManagingOrganizationTransformer('gto_id_organization', true));
        $this->addTransformer(new QuestionnaireTaskInfoTransformer($currentUri));
        $this->addTransformer(new QuestionnaireReferenceTransformer('focus'));
    }
}
