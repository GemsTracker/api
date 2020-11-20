<?php


namespace Gems\Rest\Fhir\Model\Transformer;


use Gems\Rest\Fhir\Endpoints;

class PatientManagingOrganizationTransformer extends \MUtil_Model_ModelTransformerAbstract
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
        if (isset($filter['managingOrganization'])) {
            $value = (int)str_replace(['Organization/', Endpoints::ORGANIZATION], '', $filter['managingOrganization']);
            $filter['gr2o_id_organization'] = $value;

            unset($filter['managingOrganization']);
        }
        if (isset($filter['organization'])) {
            $value = (int)str_replace(['Organization/', Endpoints::ORGANIZATION], '', $filter['organization']);
            $filter['gr2o_id_organization'] = $value;

            unset($filter['organization']);
        }

        if (isset($filter['organization_name'])) {
            $value = $filter['organization_name'];
            if ($model instanceof \MUtil_Model_DatabaseModelAbstract) {
                $adapter = $model->getAdapter();
                $value = $adapter->quote($value);
            }
            $filter[] = "gor_name LIKE '%".$value."%'";

            unset($filter['organization_name']);
        }

        if (isset($filter['organization_code'])) {
            $value = $filter['organization_code'];
            $filter['gor_code'] = $value;

            unset($filter['organization_code']);
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
            $data[$key]['managingOrganization']['id'] = $item['gr2o_id_organization'];
            $data[$key]['managingOrganization']['reference'] = Endpoints::ORGANIZATION . $item['gr2o_id_organization'];
        }

        return $data;
    }
}
