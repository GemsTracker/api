<?php

namespace Pulse\Api\Fhir\Model\Transformer;

use Gems\Util\SiteUtil;

class QuestionnaireTaskInfoTransformer extends \Gems\Rest\Fhir\Model\Transformer\QuestionnaireTaskInfoTransformer
{
    private $currentLanguage;

    public function __construct(
        \Zend_Db_Adapter_Abstract $db,
        SiteUtil $siteUtil,
        $currentUri = null,
        $currentLanguage = 'en'
    )
    {
        parent::__construct($db, $siteUtil, $currentUri);

        $this->currentLanguage = $currentLanguage;
    }

    protected function getExternalSurveyName($surveyInfo, $language)
    {
        $select = $this->db->select();
        $select->from('gems__translations', ['gtrs_translation'])
            ->where('gtrs_table = ?', 'gems__surveys')
            ->where('gtrs_field = ?', 'gsu_external_description')
            ->where('gtrs_keys = ?', $surveyInfo['id'])
            ->where('gtrs_iso_lang = ?', $language);

        $result = $this->db->fetchOne($select);

        if ($result) {
            return $result;
        }

        return $surveyInfo['display'];
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        $data = parent::transformLoad($model, $data, $new, $isPostData);

        foreach($data as $key => $row) {
            $surveyInfo = $row['focus'];

            $data[$key]['info'][] = [
                'type' => 'external-survey-name',
                'value' => $this->getExternalSurveyName($surveyInfo, $this->currentLanguage),
            ];
        }

        return $data;
    }
}