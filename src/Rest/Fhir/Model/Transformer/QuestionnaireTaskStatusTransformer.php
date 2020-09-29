<?php

namespace Gems\Rest\Fhir\Model\Transformer;


class QuestionnaireTaskStatusTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$row) {
            $now = new \MUtil_Date;
            $validFrom = null;
            if ($row['gto_valid_from'] && !($row['gto_valid_from'] instanceof \MUtil_Date)) {
                $validFrom = new \MUtil_Date($row['gto_valid_from']);
            }

            if ($validFrom === null || $now->isEarlier($validFrom)) {
                $data[$key]['status'] = 'draft';
                continue;
            }

            $validUntil = null;
            if ($row['gto_valid_until'] && !($row['gto_valid_until'] instanceof \MUtil_Date)) {
                $validUntil = new \MUtil_Date($row['gto_valid_until']);
            }

            if ($now->isLaterOrEqual($validFrom) && $now->isEarlier($validUntil) && $row['grc_success'] == 1) {
                $data[$key]['status'] = 'requested';
                continue;
            }

            if ($row['gto_completion_time'] !== null && $row['grc_success'] == 1) {
                $data[$key]['status'] = 'completed';
                continue;
            }

            if ($row['gto_completion_time'] === null && $row['grc_success'] == 1 && $now->isLater($validUntil)) {
                $data[$key]['status'] = 'rejected';
                continue;
            }

            if ($row['gto_completion_time'] === null && $row['grc_success'] == 1 && $row['gto_start_time'] !== null) {
                $data[$key]['status'] = 'in-progress';
                continue;
            }

            $data[$key]['status'] = 'unknown';
        }

        return $data;
    }
}
