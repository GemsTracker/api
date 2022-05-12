<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Model;

use Pulse\Api\Emma\Fhir\Model\Transformer\EncounterConditionTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\EncounterOrganizationTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\EncounterPatientTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\EncounterPeriodTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\EncounterPractitionerTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\EncounterStatusTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ExistingEncounterTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ResourceTypeTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\SourceIdTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\SourceTransformer;
use Pulse\Api\Emma\Fhir\Repository\AgendaStaffRepository;
use Pulse\Api\Emma\Fhir\Repository\AppointmentRepository;
use Pulse\Api\Emma\Fhir\Repository\ConditionRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportEscrowLinkRepository;
use Pulse\Api\Emma\Fhir\Repository\OrganizationRepository;
use Pulse\Api\Repository\RespondentRepository;
use Pulse\Model\ModelUpdateDiffs;

class EncounterModel extends \Gems_Model_JoinModel
{
    use ModelUpdateDiffs;

    public function __construct(AppointmentRepository $appointmentRepository,
                                OrganizationRepository $organizationRepository,
                                AgendaStaffRepository $agendaStaffRepository,
                                ConditionRepository $conditionRepository,
                                RespondentRepository $respondentRepository,
                                EpdRepository $epdRepository,
                                ImportEscrowLinkRepository $importEscrowLinkRepository)
    {
        parent::__construct('encounterModel', 'gems__appointments', 'gap', true);

        \Gems_Model::setChangeFieldsByPrefix($this, 'gap', 1);

        $this->addTransformer(new ResourceTypeTransformer('Encounter'));
        $this->addTransformer(new SourceIdTransformer('gap_id_in_source'));
        $this->addTransformer(new SourceTransformer($epdRepository, 'gap_source'));

        $this->addTransformer(new ExistingEncounterTransformer($appointmentRepository, $epdRepository));
        $this->addTransformer(new EncounterOrganizationTransformer($organizationRepository));
        $this->addTransformer(new EncounterPatientTransformer($respondentRepository, $epdRepository));
        $this->addTransformer(new EncounterPractitionerTransformer($agendaStaffRepository, $epdRepository));
        $this->addTransformer(new EncounterStatusTransformer());
        $this->addTransformer(new EncounterPeriodTransformer());
        $this->addTransformer(new EncounterConditionTransformer($conditionRepository, $epdRepository, $importEscrowLinkRepository));
    }
}
