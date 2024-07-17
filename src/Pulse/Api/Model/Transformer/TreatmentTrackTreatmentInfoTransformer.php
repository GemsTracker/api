<?php

namespace Pulse\Api\Model\Transformer;

class TreatmentTrackTreatmentInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $loadTreatment = false;

    protected $loadedTable = false;

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['with-treatment']) && $filter['with-treatment'] == 1) {
            $this->loadTreatment = true;
            unset($filter['with-treatment']);

            if (!$this->loadedTable) {
                $model->addLeftTable(['treatmentField' => 'gems__track_fields'], ['gr2t_id_track' => 'treatmentField.gtf_id_track', 'treatmentField.gtf_field_type IN (\'treatment\', \'treatmentDiagnosis\')'], 'gtf', false);
                $model->addLeftTable(['treatmentTrackField' => 'gems__respondent2track2field'], ['gr2t_id_respondent_track' => 'treatmentTrackField.gr2t2f_id_respondent_track', 'treatmentTrackField.gr2t2f_id_field' => 'treatmentField.gtf_id_field'], 'gr2t2f', false);
                $model->addLeftTable('gems__treatments', ['treatmentTrackField.gr2t2f_value' => 'gtrt_id_treatment'], 'gtrt', false);
                $this->loadedTable = true;
            }
        }

        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key => $row) {
            if ($this->loadTreatment) {
                $treatmentId = $row['gtrt_id_treatment'];
                if ($treatmentId) {
                    $treatmentId = (int) $treatmentId;
                }
                $data[$key]['treatment'] = $treatmentId;
            }
        }

        return $data;
    }
}