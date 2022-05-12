<?php

declare(strict_types=1);


namespace PulseTest\Rest\Api\Emma\Fhir\Action;

use Gems\Rest\Repository\AccesslogRepository;
use GemsTest\Rest\Test\LegacyDbTestCase;
use Mezzio\Helper\UrlHelper;
use Pulse\Api\Emma\Fhir\Action\AppointmentResourceAction;
use Pulse\Api\Emma\Fhir\Model\AppointmentModel;
use Pulse\Api\Emma\Fhir\Repository\AgendaStaffRepository;
use Pulse\Api\Emma\Fhir\Repository\AppointmentRepository;
use Pulse\Api\Emma\Fhir\Repository\ConditionRepository;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Repository\RespondentRepository;
use Zalt\Loader\ProjectOverloader;

class AppointmentResourceActionTest extends LegacyDbTestCase
{

    protected function getCurrentUserRepository()
    {
        $currentUserRepositoryProphecy = $this->prophesize(CurrentUserRepository::class);
        return $currentUserRepositoryProphecy->reveal();
    }


    protected function getModel()
    {
        $currentUserRepository = $this->getCurrentUserRepository();

        $epdRepository = new EpdRepository($currentUserRepository);

        $respondentRepository = new RespondentRepository($this->db);
        $appointmentRepository = new AppointmentRepository($this->db);
        $agendaStaffRepository = new AgendaStaffRepository($this->db, $currentUserRepository);
        $conditionRepository = new ConditionRepository($this->db);

        return new AppointmentModel(
            $respondentRepository,
            $appointmentRepository,
            $agendaStaffRepository,
            $epdRepository,
            $conditionRepository
        );
    }

    protected function getAction()
    {
        $model = $this->getModel();

        $currentUserRepository = $this->getCurrentUserRepository();

        $event = null;

        $accessLogRepositoryProphecy = $this->prophesize(AccesslogRepository::class);
        $projectOverloaderProphecy = $this->prophesize(ProjectOverloader::class);
        $urlHelperProphecy  = $this->prophesize(UrlHelper::class);

        return new AppointmentResourceAction(
            $model,
            $currentUserRepository,
            $event,
            $accessLogRepositoryProphecy->reveal(),
            $projectOverloaderProphecy->reveal(),
            $urlHelperProphecy->reveal(),
            $this->legacyDb
        );
    }

}
