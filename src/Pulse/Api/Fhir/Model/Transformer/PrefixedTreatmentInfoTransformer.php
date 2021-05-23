<?php

namespace Pulse\Api\Fhir\Model\Transformer;

class PrefixedTreatmentInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$row) {
            $info = [];
            if (isset($row['pse_id_sedation'])) {
                $info[] = [
                    'type' => 'sedation',
                    'id' => 'A' . $row['pse_id_sedation'],
                    'value' => $row['pse_name'],
                ];
            }
            if (isset($row['gdt_id_diagnosis'])) {
                $info[] = [
                    'type' => 'diagnosis',
                    'id' => 'D' . $row['gdt_id_diagnosis'],
                    'value' => $row['gdt_diagnosis_name'],
                ];
            }

            if (count($info)) {
                $data[$key]['info'] = $info;
            }
        }
        return $data;
    }
}
