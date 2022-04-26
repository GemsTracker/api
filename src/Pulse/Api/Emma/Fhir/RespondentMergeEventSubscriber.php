<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;
use Pulse\Api\Emma\Fhir\Event\RespondentMergeEvent;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportLogRepository;
use Pulse\Respondent\Accounts;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zalt\Loader\ProjectOverloader;

class RespondentMergeEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var ImportLogRepository
     */
    protected $importLogRepository;
    /**
     * @var Adapter
     */
    protected $db;
    /**
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;

    public function __construct(ImportLogRepository $importLogRepository, Adapter $db, CurrentUserRepository $currentUserRepository)
    {
        $this->importLogRepository = $importLogRepository;
        $this->db = $db;
        $this->currentUserRepository = $currentUserRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            'respondent.merge' => [
                ['fileLogRespondentMerge', 0],
                ['dbLogRespondentMerge', 0]
            ],
        ];
    }

    public function dbLogRespondentMerge(RespondentMergeEvent $event)
    {
        $table = new TableGateway('gems__respondent_merge_list', $this->db);
        $data = [
            'grml_old_patient_nr' => $event->getOldPatientNr(),
            'grml_new_patient_nr' => $event->getNewPatientNr(),
            'grml_epd' => $event->getEpd(),
            'grml_info' => $this->getMessage($event),
            'grml_status' => $event->getStatus(),
            'grml_changed' => new Expression('NOW()'),
            'grml_changed_by' => $this->currentUserRepository->getUserId(),
            'grml_created' => new Expression('NOW()'),
            'grml_created_by' => $this->currentUserRepository->getUserId(),
        ];

        $table->insert($data);
    }

    protected function getMessage(RespondentMergeEvent $event)
    {
        switch ($event->getStatus()) {
            case 'error':
                return sprintf(
                    'Patient nr %s already exists for epd %s. SSN %s is already known in patient nr %s. Patient %s not saved!!',
                    $event->getNewPatientNr(),
                    $event->getEpd(),
                    $event->getSsn(),
                    $event->getOldPatientNr(),
                    $event->getNewPatientNr()
                );
            case 'old-deleted':
                return sprintf(
                    'Existing patient nr %s was deleted. Its ssn has been removed. It can be merged with %s',
                    $event->getOldPatientNr(),
                    $event->getNewPatientNr()
                );
            default:
                return null;
        }
    }

    public function fileLogRespondentMerge(RespondentMergeEvent $event)
    {
        switch ($event->getStatus()) {
            case 'error':
                $level = 'error';
                break;
            case 'old-deleted':
                $level = 'alert';
                break;
            default:
                $level = null;
        }

        $message = $this->getMessage($event);

        if ($message !== null) {
            $log = $this->importLogRepository->getImportLogger('respondent-merge');
            $log->log($level, $message);
        }
    }
}
