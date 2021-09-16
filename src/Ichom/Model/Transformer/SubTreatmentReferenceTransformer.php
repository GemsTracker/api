<?php

namespace Ichom\Model\Transformer;


class SubTreatmentReferenceTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $rowIndex=>$row) {
            if (isset($row['treatments'])) {
                $newTreatments = [];
                foreach ($row['treatments'] as $subRow) {
                    $newSubRow = [];
                    if (isset($subRow['gtrt_id_treatment'], $subRow['gtrt_name'])) {
                        $newSubRow['id'] = (int)$subRow['gtrt_id_treatment'];
                        $newSubRow['name'] = $subRow['gtrt_name'];
                        $newSubRow['reference'] = 'ichom/treatment/' . $subRow['gtrt_id_treatment'];
                    }
                    if (isset($subRow['gtrt_external_name'])) {
                        $newSubRow['externalName'] = $subRow['gtrt_external_name'];
                    }
                    $newTreatments[] = $newSubRow;
                }
            }
            $data[$rowIndex]['treatments'] = $newTreatments;
        }
        return $data;
    }
}
