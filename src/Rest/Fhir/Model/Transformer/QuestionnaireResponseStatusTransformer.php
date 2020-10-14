<?php

namespace Gems\Rest\Fhir\Model\Transformer;


class QuestionnaireResponseStatusTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected function getFilterPartFromStatus($status)
    {
        switch($status) {
            case 'completed':
                return '(gto_completion_time IS NOT NULL AND grc_success = 1)';
            case 'entered-in-error':
                return '(grc_success = 0)';
            case 'in-progress':
                return '(gto_completion_time IS NULL AND grc_success = 1 AND gto_start_time IS NOT NULL AND (gto_valid_from IS NULL OR gto_valid_from > NOW())';
            case 'stopped':
                return '(gto_completion_time IS NULL AND grc_success = 1 AND gto_start_time IS NOT NULL AND (gto_valid_from < NOW())';
        }
        return null;
    }

    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        if (isset($filter['status'])) {
            if (is_array($filter['status'])) {
                $filterParts = [];
                foreach($filter['status'] as $status) {
                    $filterParts[] = $this->getFilterPartFromStatus($status);
                }
                if (count($filterParts)) {
                    $filter[] = '(' . join(' OR ', $filterParts) . ')';
                }
            } else {
                $filterPart = $this->getFilterPartFromStatus($filter['status']);
                if ($filterPart) {
                    $filter[] = $filterPart;
                }
            }
            unset($filter['status']);
        }

        return $filter;
    }

    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach ($data as $key => $row) {
            if ($row['gto_completion_time'] !== null && $row['grc_success'] == 1) {
                $data[$key]['status'] = 'completed';
                continue;
            }

            if ($row['grc_success'] == 0) {
                $data[$key]['status'] = 'entered-in-error';
            }

            $validUntil = null;
            $now = new \MUtil_Date;
            if ($row['gto_valid_until'] && !($row['gto_valid_until'] instanceof \MUtil_Date)) {
                $validUntil = new \MUtil_Date($row['gto_valid_until']);
            }

            if ($row['gto_completion_time'] === null && $row['grc_success'] == 1 && $row['gto_start_time'] !== null) {
                if ($now->isLater($validUntil)) {
                    $data[$key]['status'] = 'stopped';
                } else {
                    $data[$key]['status'] = 'in-progress';
                }
                continue;
            }
        }

        return $data;
    }
}
