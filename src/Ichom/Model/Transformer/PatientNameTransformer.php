<?php

namespace Ichom\Model\Transformer;


class PatientNameTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$row) {
            $nameParts = [];
            if (isset($row['grs_first_name']) && !empty($row['grs_first_name'])) {
                $nameParts[] = $row['grs_first_name'];
            } elseif (isset($row['grs_initials_name']) && !empty($row['grs_initials_name'])) {
                $nameParts[] = $row['grs_initials_name'];
            }

            if (isset($row['grs_surname_prefix']) && !empty($row['grs_surname_prefix'])) {
                $nameParts[] = $row['grs_surname_prefix'];
            }

            if (isset($row['grs_last_name']) && !empty($row['grs_last_name'])) {
                $nameParts[] = $row['grs_last_name'];
            }

            if (count($nameParts)) {
                $data[$key]['patientFullName'] = join(' ', $nameParts);
            }
        }

        return $data;
    }
}
