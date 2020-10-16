<?php

namespace Gems\Rest\Fhir\Model\Transformer;


class QuestionnaireTaskInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['roundDescription'])) {
            $filter['gto_round_description'] = $filter['roundDescription'];
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
                'value' => $loginUrl . '/ask/forward/id/' . $row['gto_id_token'],
            ];

            $data[$key]['info'] = $info;
        }

        return $data;
    }

    protected function getLoginUrl($row)
    {
        if (array_key_exists('gor_url_base', $row) && $baseUrls = explode(' ', $row['gor_url_base'])) {
            $baseUrl = reset($baseUrls);
            return $baseUrl;
        }
        return 'https://pulse.equipezorgbedrijven.nl';
    }
}
