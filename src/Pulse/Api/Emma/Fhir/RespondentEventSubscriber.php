<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir;


use Pulse\Api\Emma\Fhir\Event\RespondentMergeEvent;
use Pulse\Api\Emma\Fhir\Event\SavedModel;
use Pulse\Respondent\Accounts;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zalt\Loader\ProjectOverloader;

class RespondentEventSubscriber implements EventSubscriberInterface
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
            'model.respondentModel.saved' => [
                ['updateTracks', -10],
                ['checkAccounts', -15],
            ],
            'model.encounterModel.saved' => [
                ['updateTracks', -10],
                ['checkAccounts', -15],
            ],
        ];
    }

    public function checkAccounts(SavedModel $event)
    {
        $data = $event->getNewData();
        //
        if (isset($data['gap_id_appointment'])) {
            $appointment = $this->getAppointment($data['gap_id_appointment']);
            $accounts = $this->getAccountRepository();
            $accounts->checkAppointmentForAccounts($appointment);
        }
    }

    protected function processAfterSaveChanges($oldValues, array $newValues)
    {
        $accountChangeFields = [
            'gr2o_email',
            'grs_phone_1',
            'grs_phone_2',
            'grs_phone_3',
        ];

        $checkAccounts = false;

        if ($oldValues !== null) {
            foreach($accountChangeFields as $checkField) {
                if (isset($newValues[$checkField])) {
                    if (!isset($oldValues[$checkField])) {
                        $checkAccounts = true;
                        break;
                    }
                    if ($oldValues[$checkField] !== $newValues[$checkField]) {
                        $checkAccounts = true;
                        break;
                    }
                }
            }
        }

        if ($checkAccounts) {
            $accountsRepository = $this->getAccountRepository();

            if (isset($oldValues['gr2o_id_user'], $oldValues['gr2o_id_organization'])) {
                $accountsRepository->updateAccountsForRespondent($oldValues['gr2o_id_user'], $oldValues['gr2o_id_organization']);
            }
            // Queue the update instead of doing it directly
            /*
            \Pulse\Queue\Queue::setCurrentUserId($this->currentUser->getUserId());
            \Pulse\Queue\Queue::add('Account\\CheckAccountsForRespondent',
                [$newValues['gr2o_id_user'], $newValues['gr2o_id_organization']],
                $this->currentUser->getUserId(), null);*/
        }
    }

    protected function getAccountRepository()
    {
        if (!$this->accountRepository) {
            $this->overLoader->create('Respondent\\Accounts');
        }
        return $this->accountRepository;
    }
}
