<?php

namespace Gems\Rest\Fhir\Model\Transformer;


class CarePlanPeriodTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach ($data as $key => $row) {
            $validFrom = null;
            if ($row['gr2t_start_date']) {
                $validFrom = $row['gr2t_start_date'];
                if ($validFrom instanceof \MUtil_Date) {
                    $validFrom = $validFrom->getTimestamp();
                }

                if (!$validFrom instanceof \DateTimeImmutable) {
                    $validFrom = new \DateTimeImmutable($validFrom);
                }
            }

            $data[$key]['period']['start'] = null;
            if ($validFrom instanceof \DateTimeImmutable) {
                $data[$key]['period']['start'] = $validFrom->format(\DateTime::ATOM);
            }

            $validUntil = null;
            if ($row['gr2t_end_date']) {
                $validUntil = $row['gr2t_end_date'];
                if ($validUntil instanceof \MUtil_Date) {
                    $validUntil = $validUntil->getTimestamp();
                }

                if (!$validUntil instanceof \DateTimeImmutable) {
                    $validUntil = new \DateTimeImmutable($validUntil);
                }
            }

            $data[$key]['period']['end'] = null;
            if ($validUntil instanceof \DateTimeImmutable) {
                $data[$key]['period']['end'] = $validUntil->format(\DateTime::ATOM);
            }
        }
        return $data;
    }
}
