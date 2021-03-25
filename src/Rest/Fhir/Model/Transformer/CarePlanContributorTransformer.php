<?php

namespace Gems\Rest\Fhir\Model\Transformer;

use Gems\Rest\Fhir\Endpoints;

class CarePlanContributorTransformer extends \MUtil_Model_ModelTransformerAbstract
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
        // Organization filters
        if (isset($filter['organization'])) {
            $value = (int)str_replace(['Organization/', Endpoints::ORGANIZATION], '', $filter['organization']);
            $filter['gr2t_id_organization'] = $value;

            unset($filter['organization']);
        }

        if (isset($filter['organization.name'])) {
            $value = '%'.$filter['organization.name'].'%';
            if ($model instanceof \MUtil_Model_DatabaseModelAbstract) {
                $adapter = $model->getAdapter();
                $value = $adapter->quote($value);
                $filter[] = "gor_name LIKE " . $value;
            }

            unset($filter['organization.name']);
        }

        if (isset($filter['organization.code'])) {
            $value = $filter['organization.code'];
            $filter['gor_code'] = $value;

            unset($filter['organization.code']);
        }

        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$row) {
            $organizationInfo = [
                'id' => (int)$row['gr2t_id_organization'],
                'reference' => Endpoints::ORGANIZATION . $row['gr2t_id_organization'],
                'display' => $row['gor_name'],
            ];


            $data[$key]['contributor'] = [
                $organizationInfo,
            ];
        }

        return $data;
    }
}
