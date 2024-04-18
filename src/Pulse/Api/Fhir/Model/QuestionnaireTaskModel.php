<?php

namespace Pulse\Api\Fhir\Model;

use Gems\Rest\Fhir\Model\Transformer\ManagingOrganizationTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireOwnerTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireReferenceTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskExecutionPeriodTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskForTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskStatusTransformer;
use Pulse\Api\Fhir\Model\Transformer\QuestionnaireTaskInfoTransformer;
use Pulse\Api\Fhir\Model\Transformer\QuestionnaireTaskMedicalCategoryTransformer;

class QuestionnaireTaskModel extends \Gems\Rest\Fhir\Model\QuestionnaireTaskModel
{
    /**
     * @var \Zend_Locale
     */
    protected $locale;

    public function afterRegistry()
    {
        $siteUtil = $this->util->getSites();
        $currentUri = $this->util->getCurrentURI();

        $this->addTransformer(new QuestionnaireTaskStatusTransformer());
        $this->addTransformer(new QuestionnaireTaskExecutionPeriodTransformer());
        $this->addTransformer(new QuestionnaireOwnerTransformer());
        $this->addTransformer(new QuestionnaireTaskForTransformer());
        $this->addTransformer(new ManagingOrganizationTransformer('gto_id_organization', true));
        $this->addTransformer(new QuestionnaireReferenceTransformer('focus'));
        $this->addTransformer(new QuestionnaireTaskInfoTransformer($this->db, $siteUtil, $currentUri, $this->locale->getLanguage()));
        $this->addTransformer(new QuestionnaireTaskMedicalCategoryTransformer());

        $this->set('medicalCategory', [
            'filterValue' => true
        ]);
        $this->set('medical-category', [
            'filterValue' => true
        ]);
    }
}