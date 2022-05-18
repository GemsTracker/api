<?php

namespace Gems\Rest\Fhir\Model;


use Gems\Rest\Fhir\Model\Transformer\QuestionnaireItemsTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireResponseItemsTransformer;
use Gems\Rest\Fhir\Model\Transformer\QuestionnaireSubjectTypeTransformer;

class QuestionnaireModel extends \MUtil_Model_JoinModel
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
        parent::__construct('questionnaires', 'gems__surveys', true);

        $this->addColumn(new \Zend_Db_Expr('\'Questionnaire\''), 'resourceType');

        $this->addTable('gems__groups', ['gsu_id_primary_group' => 'ggp_id_group']);

        $this->addColumn(new \Zend_Db_Expr("
        CASE
            WHEN gsu_active = 1 THEN 'active'
            WHEN gsu_active = 0 AND gsu_status IS NOT NULL THEN 'draft'
            WHEN gsu_active = 0 AND gsu_status IS NULL THEN 'retired'
            ELSE 'unknown'
        END"), 'status');

        $this->set('gsu_id_survey', 'label', 'id', 'apiName', 'id');
        $this->set('gsu_survey_name', 'label', 'name', 'apiName', 'name');
        $this->set('status', 'label', 'status');
        $this->set('gsu_changed', 'label', 'date', 'apiName', 'date');
        $this->set('gsu_survey_description', 'label', 'description', 'apiName', 'description');



    }

    public function afterRegistry()
    {
        $tracker = $this->loader->getTracker();
        $this->addTransformer(new QuestionnaireSubjectTypeTransformer());
        $this->addTransformer(new QuestionnaireItemsTransformer($tracker, $this->locale->getLanguage()));
    }
}
