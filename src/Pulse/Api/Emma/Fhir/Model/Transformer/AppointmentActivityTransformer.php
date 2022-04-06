<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;

use Pulse\Api\Emma\Fhir\Repository\AgendaActivityRepository;

/**
 * Translate HL7 description to agenda activty ID
 */
class AppointmentActivityTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var AgendaActivityRepository
     */
    protected $activityRepository;

    public function __construct(AgendaActivityRepository $activityRepository)
    {
        $this->activityRepository = $activityRepository;
    }

    protected function getActivityParts($activityDescription)
    {
        $pattern = '/ - (Links |Rechts |BDZ )?[\d]{1,3} min/m';
        $result = preg_match($pattern, $activityDescription, $matches, PREG_OFFSET_CAPTURE);
        if ($result !== 1) {
            return ['activity' => $activityDescription];
        }

        if (count($matches)) {
            $reason = substr($matches[0][0], 3);
            $activityParts = [
                'activity' => substr($activityDescription, 0, $matches[0][1]),
                'reason' => $reason,
            ];
            if (count($matches) > 1) {
                $activityParts['side'] = trim($matches[1][0]);
                $activityParts['reason'] = substr($reason, (strlen($matches[1][0])));
            }

            return $activityParts;
        }

        return null;
    }

    /**
     * From description to agenda activity ID
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $row
     * @return array
     */
    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['description'])) {
            return $row;
        }

        $organizatonId = null;
        if (isset($row['gap_id_organization'])) {
            $organizatonId = $row['gap_id_organization'];
        }

        $activityParts = $this->getActivityParts($row['description']);
        if ($activityParts !== null && isset($activityParts['activity'])) {
            $activityId = $this->activityRepository->matchActivity($activityParts['activity'], $organizatonId);
            if ($activityId) {
                $row['gap_id_activity'] = $activityId;
            }
            if (isset($activityParts['reason'])) {
                if (!isset($row['gap_info']) || !is_array($row['gap_info'])) {
                    $row['gap_info'] = [];
                }
                $row['gap_info']['reason'] = $activityParts['reason'];
            }
            if (isset($activityParts['side'])) {
                if (!isset($row['gap_info']) || !is_array($row['gap_info'])) {
                    $row['gap_info'] = [];
                }
                $row['gap_info']['side'] = $activityParts['side'];
            }
        }

        return $row;
    }
}
