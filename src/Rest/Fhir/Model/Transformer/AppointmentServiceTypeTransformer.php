<?php


namespace Gems\Rest\Fhir\Model\Transformer;


class AppointmentServiceTypeTransformer extends \MUtil_Model_ModelTransformerAbstract
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
        if (isset($filter['service-type'])) {
            $value = (int) $filter['service-type'];
            $filter['gap_id_activity'] = $value;

            unset($filter['service-type']);
        }
        if (isset($filter['serviceType'])) {
            $value = (int) $filter['serviceType'];
            $filter['gap_id_activity'] = $value;

            unset($filter['serviceType']);
        }
        if (isset($filter['service-type.display'])) {
            $value = $filter['service-type.display'];
            $filter[] = "gaa_name LIKE '%".$value."'%";

            unset($filter['service-type.display']);
        }
        if (isset($filter['serviceType.display'])) {
            $value = $filter['serviceType.display'];
            $filter[] = "gaa_name LIKE '%".$value."'%";

            unset($filter['serviceType.display']);
        }

        if (($key = array_search('service-type IS NULL', $filter)) !== false) {
            $filter[$key] = 'gaa_id_activity IS NULL';
        }
        if (($key = array_search('serviceType IS NULL', $filter)) !== false) {
            $filter[$key] = 'gaa_id_activity IS NULL';
        }
        if (($key = array_search('service-type IS NOT NULL', $filter)) !== false) {
            $filter[$key] = 'gaa_id_activity IS NOT NULL';
        }
        if (($key = array_search('serviceType IS NOT NULL', $filter)) !== false) {
            $filter[$key] = 'gaa_id_activity IS NOT NULL';
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
        foreach($data as $key=>$item) {
            if (isset($item['gap_id_activity'], $item['gaa_name'])) {
                $coding = [
                    'coding' => [
                        'code' => (int)$item['gap_id_activity'],
                        'display' => $item['gaa_name'],
                    ],
                ];
                $data[$key]['serviceType'][] = $coding;
            }
        }

        return $data;
    }
}
