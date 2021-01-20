<?php


namespace Pulse\Api\Fhir\Model\Transformer;


class TreatmentIdTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * This transform function checks the filter for
     * a) retreiving filters to be applied to the transforming data,
     * b) adding filters that are needed
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $filter
     * @return array The (optionally changed) filter
     */
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['id'])) {
            if (strpos($filter['id'], 'RT') === 0) {
                $filter['gr2t_id_respondent_track'] = (int) substr($filter['id'], 2);
            }
            if (strpos($filter['id'], 'A') === 0) {
                $filter['gap_id_appointment'] = (int) substr($filter['id'], 1);
            }

            unset($filter['id']);
        }
        return $filter;
    }
}
