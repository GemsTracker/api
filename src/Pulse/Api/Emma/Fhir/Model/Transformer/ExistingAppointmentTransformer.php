<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Gems\Rest\Exception\MissingDataException;
use Pulse\Api\Emma\Fhir\Repository\AppointmentRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;

/**
 * Match existing appointment to the current Source ID
 */
class ExistingAppointmentTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var AppointmentRepository
     */
    protected $appointmentRepository;

    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(AppointmentRepository $appointmentRepository, EpdRepository $epdRepository)
    {
        $this->appointmentRepository = $appointmentRepository;
        $this->epdRepository = $epdRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['id'])) {
            throw new MissingDataException('No source ID supplied');
        }

        $appointment = $this->appointmentRepository->getAppointmentFromSourceId($row['id'], $this->epdRepository->getEpdName());
        if ($appointment) {
            $row['exists'] = true;
            $row['gap_id_appointment'] = $appointment['gap_id_appointment'];
            $row['gap_id_organization'] = $appointment['gap_id_organization'];
        }

        return $row;
    }
}
