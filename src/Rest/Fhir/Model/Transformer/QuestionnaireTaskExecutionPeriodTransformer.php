<?php

namespace Gems\Rest\Fhir\Model\Transformer;


class QuestionnaireTaskExecutionPeriodTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach ($data as $key => $row) {
            $validFrom = null;
            if ($row['gto_valid_from']) {
                $validFrom = $row['gto_valid_from'];
                if ($validFrom instanceof \MUtil_Date) {
                    $validFrom = $validFrom->getTimestamp();
                }

                if (!$validFrom instanceof \DateTimeImmutable) {
                    $validFrom = new \DateTimeImmutable($validFrom);
                }
            }

            $data[$key]['executionPeriod']['start'] = null;
            if ($validFrom instanceof \DateTimeImmutable) {
                $data[$key]['executionPeriod']['start'] = $validFrom->format(\DateTime::ATOM);
            }

            $validUntil = null;
            if ($row['gto_valid_until']) {
                $validUntil = $row['gto_valid_until'];
                if ($validUntil instanceof \MUtil_Date) {
                    $validUntil = $validUntil->getTimestamp();
                }

                if (!$validUntil instanceof \DateTimeImmutable) {
                    $validUntil = new \DateTimeImmutable($validUntil);
                }
            }

            $data[$key]['executionPeriod']['end'] = null;
            if ($validUntil instanceof \DateTimeImmutable) {
                $data[$key]['executionPeriod']['end'] = $validUntil->format(\DateTime::ATOM);
            }
        }
        return $data;
    }
}
