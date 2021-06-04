<?php

namespace Gems\Rest\Fhir\Model\Transformer;


use Gems\Rest\Fhir\Endpoints;

class QuestionnaireTaskInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var null
     */
    protected $currentUri;

    public function __construct($currentUri = null)
    {
        $this->currentUri = $currentUri;
    }

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['roundDescription'])) {
            $filter['gto_round_description'] = $filter['roundDescription'];
            unset($filter['roundDescription']);
        }

        if (isset($filter['track'])) {
            $filter['gto_id_track'] = $filter['track'];
            unset($filter['track']);
        }

        if (isset($filter['track_name'])) {
            $filter['gtr_track_name'] = $filter['trackName'];
            unset($filter['trackName']);
        }

        if (isset($filter['track_code'])) {
            $filter['gtr_code'] = $filter['track_code'];
            unset($filter['track_code']);
        }

        if (isset($filter['carePlan'])) {
            $filter['gto_id_respondent_track'] = $filter['carePlan'];
            unset($filter['carePlan']);
        }

        if (isset($filter['respondentTrackId'])) {
            $filter['gto_id_respondent_track'] = $filter['respondentTrackId'];
            unset($filter['respondentTrackId']);
        }

        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$row) {
            $info = [];
            if (isset($row['gto_round_description'])) {
                $info[] = [
                    'type' => 'roundDescription',
                    'value' => $row['gto_round_description'],
                ];
            }

            if (isset($row['gto_round_order'])) {
                $info[] = [
                    'type' => 'roundOrder',
                    'value' => $row['gto_round_order'],
                ];
            }

            $loginUrl = $this->getLoginUrl($row);
            $info[] = [
                'type' => 'url',
                'value' => $loginUrl . '/ask/to-survey/id/' . $row['gto_id_token'],
            ];

            if (isset($row['gto_id_track'])) {
                $info[] = [
                    'type' => 'track',
                    'value' => $row['gtr_track_name'],
                ];
            }

            if (isset($row['gto_id_respondent_track'])) {
                $info[] = [
                    'type' => 'CarePlan',
                    'id' => $row['gto_id_respondent_track'],
                    'reference' => Endpoints::CARE_PLAN . $row['gto_id_respondent_track'],
                    'display' => $row['gtr_track_name'],
                ];
            }

            $data[$key]['info'] = $info;
        }

        return $data;
    }

    protected function getLoginUrl($row)
    {
        if (array_key_exists('gor_url_base', $row) && $baseUrls = explode(' ', $row['gor_url_base'])) {
            $baseUrl = reset($baseUrls);
            if (!empty($baseUrl)) {
                return $baseUrl;
            }
        }

        return $this->currentUri;
    }
}
