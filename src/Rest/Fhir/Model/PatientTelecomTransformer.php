<?php


namespace Gems\Rest\Fhir\Model;


class PatientTelecomTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach ($data as $key => $item) {
            $elements = [];
            if (isset($item['gr2o_email'])) {
                $elements[] = ['system' => 'email', 'value' => $item['gr2o_email']];
            }

            if (isset($item['grs_phone_1'])) {
                $elements[] = ['system' => 'phone', 'value' => $item['grs_phone_1']];
            }

            $data[$key]['telecom'] = $elements;
        }

        return $data;
    }
}
