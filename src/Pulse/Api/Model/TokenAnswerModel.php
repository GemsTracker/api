<?php

namespace Pulse\Api\Model;

use Gems\Rest\Fhir\Model\Transformer\ManagingOrganizationTransformer;
use Gems\Rest\Fhir\Model\Transformer\PatientReferenceTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireOwnerTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireResponseStatusTransformer;
use Pulse\Api\Model\Transformer\TokenAnswerTransformer;

class TokenAnswerModel extends \MUtil_Model_JoinModel
{
    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     * @var \Gems_User_User
     */
    protected $currentUser;
    public function __construct()
    {
        parent::__construct('tokenAnswers', 'gems__tokens', true);
        $this->addTable('gems__respondent2org', ['gr2o_id_user' => 'gto_id_respondent', 'gr2o_id_organization' => 'gto_id_organization']);
        $this->addTable('gems__reception_codes', ['gto_reception_code' => 'grc_id_reception_code']);
        $this->addTable('gems__organizations', ['gto_id_organization' => 'gor_id_organization']);
        $this->addTable('gems__surveys', ['gto_id_survey' => 'gsu_id_survey']);

        $this->set('gto_id_token', [
            'label' => 'id',
            'apiName' => 'id',
        ]);

        $this->set('gto_id_organization', [
            'label' => 'organizationId',
            'apiName' => 'organizationId',
        ]);

        $this->set('status', [
            'label' => 'status'
        ]);
        $this->set('subject', [
            'label' => 'subject'
        ]);
        $this->set('organization', [
            'label' => 'organization'
        ]);

        $this->addTransformer(new QuestionnaireResponseStatusTransformer());
        $this->addTransformer(new PatientReferenceTransformer('subject'));
        $this->addTransformer(new ManagingOrganizationTransformer('gto_id_organization', true, 'organization'));
    }

    public function afterRegistry()
    {
        $tracker = $this->loader->getTracker();
        $this->addTransformer(new TokenAnswerTransformer($tracker, $this->locale->getLanguage(), $this->currentUser->getUserId()));
    }
}