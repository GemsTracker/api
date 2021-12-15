<?php

namespace Ichom\Model\Transformer;


use Gems\Rest\Fhir\Model\Transformer\RespondentTrackFields;
use Gems\Tracker\Field\FieldAbstract;

/**
 * Adds respondent track fields as flat data
 */
class FlatRespondentTrackFieldTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    use RespondentTrackFields;

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        $explodeFieldTypes = ['multiselect'];
        foreach($data as $key=>$trackRow) {
            $respondentTrackId = $trackRow['gr2t_id_respondent_track'];
            $trackFields = $this->getTrackfields($respondentTrackId);
            $trackFieldDataPairs = [];
            foreach($trackFields as $trackField) {
                $id = $trackField['id'];
                if (isset($trackField['gtf_field_code'])) {
                    $id = $trackField['gtf_field_code'];
                }
                $trackFieldDataPairs[$id] = $trackField['gr2t2f_value'];

                if (in_array($trackField['gtf_field_type'], $explodeFieldTypes)) {
                    $trackFieldDataPairs[$id] = explode(FieldAbstract::FIELD_SEP, $trackField['gr2t2f_value']);
                }

                $model->set($id, ['allow_api_load' => true]);
            }
            $trackRow = array_merge($trackRow, $trackFieldDataPairs);
            $data[$key] = $trackRow;
        }

        return $data;
    }

}
