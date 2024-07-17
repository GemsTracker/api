<?php

namespace Pulse\Api\Model\Transformer;

class TreatmentTrackDiagnosisInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $loadDiagnosis = false;

    protected $loadedTable = false;

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['with-diagnosis']) && $filter['with-diagnosis'] == 1) {
            $this->loadDiagnosis = true;
            unset($filter['with-diagnosis']);

            if (!$this->loadedTable) {
                $model->addLeftTable(['diagnosisField' => 'gems__track_fields'], ['gr2t_id_track' => 'diagnosisField.gtf_id_track', 'diagnosisField.gtf_field_type' => new \Zend_Db_Expr('\'diagnosis\'')], 'gtf', false);
                $model->addLeftTable(['diagnosisTrackField' => 'gems__respondent2track2field'], ['gr2t_id_respondent_track' => 'diagnosisTrackField.gr2t2f_id_respondent_track', 'diagnosisTrackField.gr2t2f_id_field' => 'diagnosisField.gtf_id_field'], 'gr2t2f', false);
                $model->addLeftTable('gems__diagnosis2track', ['diagnosisTrackField.gr2t2f_value' => 'gdt_id_diagnosis'], 'gdt', false);
                $this->loadedTable = true;
            }
        }

        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key => $row) {
            if ($this->loadDiagnosis) {
                $diagnosisId = $row['gdt_id_diagnosis'];
                if ($diagnosisId) {
                    $diagnosisId = (int) $diagnosisId;
                }
                $data[$key]['diagnosis'] = $diagnosisId;
            }
        }

        return $data;
    }
}