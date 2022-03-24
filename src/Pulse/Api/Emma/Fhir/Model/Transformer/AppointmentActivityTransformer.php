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

        $activityId = $this->activityRepository->matchActivity($row['description'], $organizatonId);
        if ($activityId) {
            $row['gap_id_activity'] = $activityId;
        }

        return $row;
    }
}
