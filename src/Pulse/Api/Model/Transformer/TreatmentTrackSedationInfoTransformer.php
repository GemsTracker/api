<?php

namespace Pulse\Api\Model\Transformer;

class TreatmentTrackSedationInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $loadSedation = false;

    protected $loadedTables = false;

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['with-sedation']) && $filter['with-sedation'] == 1) {
            $this->loadSedation = true;
            unset($filter['with-sedation']);

            if (!$this->loadedTables) {
                $model->addLeftTable(['treatmentSedationField' => 'gems__track_fields'], ['gr2t_id_track' => 'treatmentSedationField.gtf_id_track', 'treatmentSedationField.gtf_field_type' => new \Zend_Db_Expr('\'sedation\'')], 'gtf', false);
                $model->addLeftTable(['treatmentSedationTrackField' => 'gems__respondent2track2field'], ['gr2t_id_respondent_track' => 'treatmentSedationTrackField.gr2t2f_id_respondent_track', 'treatmentSedationTrackField.gr2t2f_id_field' => 'treatmentSedationField.gtf_id_field'], 'gr2t2f', false);
                $model->addLeftTable('pulse__sedations', ['treatmentSedationTrackField.gr2t2f_value' => 'pse_id_sedation'], 'pse', false);
                $this->loadedTables = true;
            }
        }
        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key => $row) {
            if ($this->loadSedation) {
                $sedation = $row['pse_id_sedation'];
                if ($sedation) {
                    $sedation = (int)$sedation;
                }
                $data[$key]['sedation'] = $sedation;
            }
        }

        return $data;
    }
}