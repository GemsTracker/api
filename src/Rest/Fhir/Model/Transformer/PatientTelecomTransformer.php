<?php


namespace Gems\Rest\Fhir\Model\Transformer;


class PatientTelecomTransformer extends \MUtil_Model_ModelTransformerAbstract
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
        if (isset($filter['email'])) {
            $filter['gr2o_email'] = $filter['email'];

            unset($filter['email']);
        }

        if (isset($filter['phone'])) {
            $filter[] = [
                'grs_phone_1' => $filter['phone'],
                'grs_phone_2' => $filter['phone'],
                'grs_phone_3' => $filter['phone'],
            ];

            unset($filter['phone']);
        }

        return $filter;
    }

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
                $elements[] = [
                    'system' => 'phone',
                    'value' => $item['grs_phone_1'],
                    'use' => 'home',
                ];
            }
            if (isset($item['grs_phone_2'])) {
                $elements[] = [
                    'system' => 'phone',
                    'value' => $item['grs_phone_2'],
                    'use' => 'work',
                ];
            }
            if (isset($item['grs_phone_3'])) {
                $elements[] = [
                    'system' => 'phone',
                    'value' => $item['grs_phone_3'],
                    'use' => 'mobile',
                ];
            }

            $data[$key]['telecom'] = $elements;
        }

        return $data;
    }
}
