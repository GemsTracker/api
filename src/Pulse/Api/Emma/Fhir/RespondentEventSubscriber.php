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
                ['checkAccounts', -15],
            ],
        ];
    }

    public function checkAccounts(SavedModel $event)
    {
        $oldValues = $event->getOldData();
        $diffs = $event->getUpdateDiffs();

        if ($oldValues !== null && count($oldValues) && count($diffs)) {
            $accountChangeFields = [
                'gr2o_patient_nr',
                'gr2o_email',
                'grs_phone_1',
                'grs_phone_2',
                'grs_phone_3',
            ];

            $checkAccounts = false;

            foreach($accountChangeFields as $checkField) {
                if (array_key_exists($checkField, $diffs)) {
                    $checkAccounts = true;
                    break;
                }
            }

            if ($checkAccounts) {
                $accountsRepository = $this->getAccountRepository();

                if (isset($oldValues['gr2o_id_user'], $oldValues['gr2o_id_organization'])) {
                    $accountsRepository->updateAccountsForRespondent($oldValues['gr2o_id_user'], $oldValues['gr2o_id_organization']);
                }
            }
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
