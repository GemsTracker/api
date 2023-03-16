<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir;


use Pulse\Api\Emma\Fhir\Event\SavedModel;
use Pulse\Api\Emma\Fhir\Repository\IntakeAnaesthesiaLinkRepository;
use Pulse\Respondent\Accounts;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zalt\Loader\ProjectOverloader;

class AppointmentEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Accounts
     */
    protected $accountRepository;

    /**
     * @var array
     */
    protected $appointments = [];

    /**
     * @var ProjectOverloader
     */
    protected $overLoader;
    /**
     * @var IntakeAnaesthesiaLinkRepository
     */
    protected $intakeAnaesthesiaLinkRepository;

    public function __construct(ProjectOverloader $overLoader, IntakeAnaesthesiaLinkRepository $intakeAnaesthesiaLinkRepository)
    {
        $this->overLoader = $overLoader;
        $this->intakeAnaesthesiaLinkRepository = $intakeAnaesthesiaLinkRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            'model.appointmentModel.saved' => [
                ['updateTracks', 30],
                ['checkIntakeAnaesthesiaLink', 25],
                ['checkAccounts', 20],
            ],
            /*'model.encounterModel.saved' => [
                ['updateTracks', 30],
                ['checkIntakeAnaesthesiaLink', 25],
                ['checkAccounts', 20],
            ],*/
        ];
    }

    public function checkAccounts(SavedModel $event)
    {
        $data = $event->getNewData();
        if (isset($data['gap_id_appointment'])) {
            $appointment = $this->getAppointment($data['gap_id_appointment']);
            $accounts = $this->getAccountRepository();
            $accounts->checkAppointmentForAccounts($appointment);
        }
    }

    public function checkIntakeAnaesthesiaLink(SavedModel $event)
    {
        $data = $event->getNewData();
        if (isset($data['gap_id_appointment'])) {
            $this->intakeAnaesthesiaLinkRepository->checkAppointmentLink($data);
        }
    }

    public function updateTracks(SavedModel $event)
    {
        $data = $event->getNewData();
        if (isset($data['gap_id_appointment'])) {
            $appointment = $this->getAppointment($data['gap_id_appointment']);
            $appointment->updateTracks();
        }
    }

    /**
     * @param $appointmentId
     * @return \Pulse_Agenda_Appointment
     */
    protected function getAppointment($appointmentId)
    {
        if (!isset($this->appointments[$appointmentId])) {
            $this->appointments[$appointmentId] = $this->overLoader->create('Agenda\\Appointment', $appointmentId);
        }
        return $this->appointments[$appointmentId];
    }

    protected function getAccountRepository()
    {
        if (!$this->accountRepository) {
            $this->accountRepository = $this->overLoader->create('Respondent\\Accounts');
        }
        return $this->accountRepository;
    }
}
