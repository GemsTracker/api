<?php

namespace Pulse\Api\Fhir\Model\Transformer;

class CarePlanMedicalCategoryFilterTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $medicalCategoryOrganizations = [80];

    protected $filterJoin = false;

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        $medicalCategoryFilter = null;
        if (isset($filter['medical-category'])) {
            $medicalCategoryFilter = $filter['medical-category'];
            unset($filter['medical-category']);

        }
        if (isset($filter['medicalCategory'])) {
            $medicalCategoryFilter = $filter['medicalCategory'];
            unset($filter['medicalCategory']);
        }

        if ($medicalCategoryFilter === null) {
            return $filter;
        }
        if (!is_array($medicalCategoryFilter)) {
            $medicalCategoryFilter = [$medicalCategoryFilter];
        }

        $organizationIds = [];
        foreach($filter as $key => $filterPart) {
            if (is_numeric($key) && is_array($filterPart) && isset($filterPart[0]['gr2o_id_organization'])) {
                foreach($filterPart as $patientIdPair) {
                    if (in_array($patientIdPair['gr2o_id_organization'], $this->medicalCategoryOrganizations)) {
                        $organizationIds[] = $patientIdPair['gr2o_id_organization'];
                    }
                }
            }
        }
        if (!count($organizationIds)) {
            return $filter;
        }

        if ($model instanceof \MUtil_Model_JoinModel ) {
            if (!$this->filterJoin) {
                $this->filterJoin = true;    
                $model->addTable(['mcrtf' => 'gems__respondent2track2field'], ['mcrtf.gr2t2f_id_respondent_track' => 'gr2t_id_respondent_track']);
                $model->addTable(['mctf' => 'gems__track_fields'], [
                    'mctf.gtf_id_track' => 'gr2t_id_track',
                    'mctf.gtf_id_field' => 'mcrtf.gr2t2f_id_field',
                    'mctf.gtf_field_type' => new \Zend_Db_Expr('\'medicalCategory\''),
                ]);
            }

            $filter['mcrtf.gr2t2f_value'] = $medicalCategoryFilter;
        }

        return $filter;
    }
}