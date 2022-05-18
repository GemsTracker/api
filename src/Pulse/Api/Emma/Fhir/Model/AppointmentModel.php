<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model;


use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentActivityTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentConditionTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentEscrowOrganizationTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentPatientTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentPractitionerTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentRequestedPeriodTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\AppointmentStatusTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ExistingAppointmentTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\JsonFieldTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ResourceTypeTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\SourceIdTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\SourceTransformer;
use Pulse\Api\Emma\Fhir\Repository\AgendaActivityRepository;
use Pulse\Api\Emma\Fhir\Repository\AgendaStaffRepository;
use Pulse\Api\Emma\Fhir\Repository\AppointmentRepository;
use Pulse\Api\Emma\Fhir\Repository\ConditionRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\EscrowOrganizationRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportEscrowLinkRepository;
use Pulse\Api\Repository\RespondentRepository;
use Pulse\Model\ModelUpdateDiffs;

class AppointmentModel extends \Gems_Model_JoinModel
{
    use ModelUpdateDiffs;

    public function __construct(RespondentRepository $respondentRepository,
                                AppointmentRepository $appointmentRepository,
                                AgendaStaffRepository $agendaStaffRepository,
                                AgendaActivityRepository $agendaActivityRepository,
                                EpdRepository $epdRepository,
                                ConditionRepository $conditionRepository,
                                ImportEscrowLinkRepository $importEscrowLinkRepository,
                                EscrowOrganizationRepository $escrowOrganizationRepository)
    {
        parent::__construct('appointmentModel', 'gems__appointments', 'gap', true);

        \Gems_Model::setChangeFieldsByPrefix($this, 'gap', 1);

        $this->addTransformer(new ResourceTypeTransformer('Appointment'));
        $this->addTransformer(new SourceIdTransformer('gap_id_in_source'));
        $this->addTransformer(new SourceTransformer($epdRepository, 'gap_source'));


        $this->addTransformer(new ExistingAppointmentTransformer($appointmentRepository, $epdRepository));

        $this->addTransformer(new AppointmentEscrowOrganizationTransformer($escrowOrganizationRepository));
        $this->addTransformer(new AppointmentPatientTransformer($respondentRepository, $epdRepository));
        $this->addTransformer(new AppointmentPractitionerTransformer($agendaStaffRepository, $epdRepository));
        $this->addTransformer(new AppointmentStatusTransformer());
        $this->addTransformer(new AppointmentActivityTransformer($agendaActivityRepository));
        $this->addTransformer(new AppointmentRequestedPeriodTransformer());
        $this->addTransformer(new AppointmentConditionTransformer($conditionRepository, $epdRepository, $importEscrowLinkRepository));
        $this->addTransformer(new JsonFieldTransformer(['gap_info']));
    }

    public function afterRegistry()
    {
        $this->set('gap_admission_time', [
            'label' => 'Start time',
            'apiName' => 'start',
        ]);
        $this->set('gap_discharge_time', [
            'label' => 'End time',
            'apiName' => 'end',
        ]);
    }
}
