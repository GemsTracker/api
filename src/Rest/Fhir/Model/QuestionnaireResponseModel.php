<?php

namespace Gems\Rest\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\ManagingOrganizationTransformer;
use Gems\Rest\Fhir\Model\Transformer\PatientReferenceTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireResponseItemsTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireOwnerTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireResponseStatusTransformer;

class QuestionnaireResponseModel extends \Gems_Model_JoinModel
{
    /**
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * @var \Zend_Locale
     */
    public $locale;

    public function __construct()
    {
        parent::__construct('questionnaireresponse', 'gems__tokens', 'gto', true);
        $this->addTable('gems__respondent2org', ['gr2o_id_user' => 'gto_id_respondent', 'gr2o_id_organization' => 'gto_id_organization']);
        $this->addTable('gems__reception_codes', ['gto_reception_code' => 'grc_id_reception_code', 'gto_completion_time IS NOT NULL']);
        $this->addTable('gems__surveys', ['gto_id_survey' => 'gsu_id_survey']);
        $this->addTable('gems__groups', ['gsu_id_primary_group' => 'ggp_id_group']);
        $this->addTable('gems__organizations', ['gto_id_organization' => 'gor_id_organization']);
        $this->addLeftTable('gems__staff', ['gto_by' => 'gsf_id_user']);
        $this->addLeftTable('gems__agenda_staff', ['gsf_id_user' => 'gas_id_user']);
        $this->addLeftTable('gems__respondent_relations', ['gto_id_respondent' => 'grr_id_respondent', 'gto_id_relation' => 'grr_id']);

        $this->addColumn(new \Zend_Db_Expr('\'QuestionnaireResponse\''), 'resourceType');

        $this->set('resourceType', 'label', 'resourceType');
        $this->set('gto_id_token', 'label', 'id', 'apiName', 'id');

        $this->set('gto_completion_time', 'label', 'authored', 'apiName', 'authored');

        $this->set('status', 'label', 'status');
        $this->set('subject', 'label', 'subject');
        $this->set('source', 'label', 'source');
        $this->set('author', 'label', 'author');
        $this->set('item', 'label', 'item');


        $this->addTransformer(new QuestionnaireResponseStatusTransformer());
        $this->addTransformer(new PatientReferenceTransformer('subject'));
        $this->addTransformer(new QuestionnaireOwnerTransformer('source'));
        $this->addTransformer(new ManagingOrganizationTransformer('gto_id_organization', true, 'author'));
        $this->addFilter(['gto_completion_time IS NOT NULL']);
    }

    public function afterRegistry()
    {
        $tracker = $this->loader->getTracker();
        $this->addTransformer(new QuestionnaireResponseItemsTransformer($tracker, $this->locale->getLanguage()));
    }
}
