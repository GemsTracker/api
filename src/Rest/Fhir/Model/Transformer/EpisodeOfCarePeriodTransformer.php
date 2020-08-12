<?php


namespace Gems\Rest\Fhir\Model\Transformer;


class EpisodeOfCarePeriodTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['start'])) {
            $filter['gec_startdate'] = $filter['start'];
            unset($filter['start']);
        }
        if (isset($filter['end'])) {
            $filter['gec_enddate'] = $filter['end'];
            unset($filter['end']);
        }

        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$item) {
            $period = [];
            if (isset($item['gec_startdate'])) {
                $period['start'] = $item['gec_startdate'];
            }
            if (isset($item['gec_enddate'])) {
                $period['end'] = $item['gec_enddate'];
            }

            if (count($period)) {
                $data[$key]['period'] = $period;
            }
        }

        return $data;
    }
}
