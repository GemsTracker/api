<?php


namespace Gems\Rest\Fhir\Model\Transformer;


class LocationAddressTransformer extends \MUtil_Model_ModelTransformerAbstract
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
        if (isset($filter['address'])) {
            $value = $filter['address'];
            $filter[] = "(glo_address_1 = '".$value."')
             OR (glo_city = '".$value."')
             OR (glo_iso_country = '".$value."')
             OR (glo_zipcode = '".$value."')
            ";

            unset($filter['address']);
        }

        if (isset($filter['address-city'])) {
            $filter['glo_city'] = $filter['address-city'];
            unset($filter['address-city']);
        }
        if (isset($filter['address-country'])) {
            $filter['glo_iso_country'] = $filter['address-country'];
            unset($filter['address-country']);
        }
        if (isset($filter['address-postalcode'])) {
            $filter['glo_zipcode'] = $filter['address-postalcode'];
            unset($filter['address-postalcode']);
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
            $itemAddress = [
                'use' => 'work',
            ];

            if (isset($item['glo_address_1'])) {
                $itemAddress['line'][] = $item['glo_address_1'];
                if (isset($item['glo_address_2'])) {
                    $itemAddress['line'][] = $item['glo_address_2'];
                }
            }
            if (isset($item['glo_city'])) {
                $itemAddress['city'] = $item['glo_city'];
            }
            if (isset($item['glo_iso_country'])) {
                $itemAddress['country'] = $item['glo_iso_country'];
            }
            if (isset($item['glo_zipcode'])) {
                $itemAddress['postalCode'] = $item['glo_zipcode'];
            }

            $data[$key]['address'] = $itemAddress;
        }

        return $data;

    }
}
