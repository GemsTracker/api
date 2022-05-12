<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir;


use Pulse\Api\Emma\Fhir\Event\SavedModel;
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

    public function __construct(ProjectOverloader $overLoader)
    {
        $this->overLoader = $overLoader;
    }

    public static function getSubscribedEvents()
    {
        return [
            'model.appointmentModel.saved' => [
                'updateTracks', 30,
                'checkAccounts', 25,
            ],
            'model.encounterModel.saved' => [
                'updateTracks', 30,
                'checkAccounts', 25,
            ],
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
