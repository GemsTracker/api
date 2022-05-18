<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;
use Pulse\Api\Emma\Fhir\Event\DeleteResourceEvent;
use Pulse\Api\Emma\Fhir\Event\RespondentMergeEvent;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportLogRepository;
use Pulse\Api\Repository\RespondentRepository;
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
    /**
     * @var RespondentRepository
     */
    protected $respondentRepository;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(ImportLogRepository $importLogRepository,
                                Adapter $db,
                                CurrentUserRepository $currentUserRepository,
                                RespondentRepository $respondentRepository,
                                EpdRepository $epdRepository)
    {
        $this->importLogRepository = $importLogRepository;
        $this->db = $db;
        $this->currentUserRepository = $currentUserRepository;

        $this->respondentRepository = $respondentRepository;
        $this->epdRepository = $epdRepository;
    }

    public static function getSubscribedEvents()
    {

        return [
            'respondent.merge' => [
                ['fileLogRespondentMerge', 0],
                ['dbLogRespondentMerge', 0]
            ],

            'resource.respondentModel.deleted' => [
                ['checkMergeable', -15],
            ]
        ];
    }

    public function checkMergeable(DeleteResourceEvent $event)
    {
        $resourceId = $event->getResourceId();

        $respondentInfo = $this->getRespondentInfoFromEpdId($resourceId, $this->epdRepository->getEpdName());

        $sql = new Sql($this->db);
        $select = $sql->select('gems__respondent_merge_list');
        $select
            ->where
            ->nest()
                ->equalTo('grml_old_patient_nr', $respondentInfo['gr2o_patient_nr'])
                ->or
                ->equalTo('grml_new_patient_nr', $respondentInfo['gr2o_patient_nr'])
            ->unnest()
            ->equalTo('grml_epd', $this->epdRepository->getEpdName());

        $select->order('grml_created DESC');

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if (!$result->valid() || !$result->current()) {
            return null;
        }

        $mergeInfo = $result->current();

        if (isset($mergeInfo['grml_status']) && $mergeInfo['grml_status'] === 'new-ssn-removed') {

            $oldPatientNr = $mergeInfo['grml_old_patient_nr'];
            $newPatientNr = $mergeInfo['grml_new_patient_nr'];
            if ($newPatientNr === $respondentInfo['gr2o_patient_nr']) {
                $oldPatientNr = $mergeInfo['grml_new_patient_nr'];
                $newPatientNr = $mergeInfo['grml_old_patient_nr'];
            }

            $ssn = $respondentInfo['grs_ssn'];
            if ($ssn === null) {
                $newRespondentInfo = $this->respondentRepository->getRespondentInfoFromPatientNr($newPatientNr, $this->epdRepository->getEpdName());
                $ssn = $newRespondentInfo['grs_ssn'];
            }

            $respondentMergeEvent = new RespondentMergeEvent();
            $respondentMergeEvent->setOldPatientNr($oldPatientNr);
            $respondentMergeEvent->setNewPatientNr($newPatientNr);
            $respondentMergeEvent->setSsn($ssn);
            $respondentMergeEvent->setEpd($this->epdRepository->getEpdName());
            $respondentMergeEvent->setStatus('old-deleted');

            // Remove old user SSN
            $comment = $respondentInfo['gr2o_comments'] .= sprintf("\nSSN %s removed in favor of patientnr %s", $ssn, $mergeInfo['grml_new_patient_nr']);
            $this->respondentRepository->removeSsnFromRespondent($respondentInfo['gr2o_id_user'], $comment);

            // Add new user SSN
            $this->respondentRepository->updateRespondentFromPatientnr($newPatientNr,
                $this->epdRepository->getEpdName(),
                [
                    'grs_ssn' => $ssn,
                ]
            );

            $this->dbLogRespondentMerge($respondentMergeEvent);
            $this->fileLogRespondentMerge($respondentMergeEvent);
        }
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
                    'Existing patient %s was deleted. Its ssn has been removed. It can be merged with %s',
                    $event->getOldPatientNr(),
                    $event->getNewPatientNr()
                );
            case 'new-ssn-removed':
                return sprintf(
                    'Patient nr %s already exists for epd %s. SSN %s is already known in patient nr %s. SSN of Patient %s has been removed!!',
                    $event->getNewPatientNr(),
                    $event->getEpd(),
                    $event->getSsn(),
                    $event->getOldPatientNr(),
                    $event->getNewPatientNr()
                );
            default:
                return null;
        }
    }

    public function getRespondentInfoFromEpdId($epdId, $epd)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2org')
            ->join('gems__respondents', 'gr2o_id_user = grs_id_user', ['grs_ssn'])
            ->join('gems__organizations', 'gor_id_organization = gr2o_id_organization', [])
            ->columns(['gr2o_id_user', 'gr2o_patient_nr', 'gr2o_comments'])
            ->where([
                'gr2o_epd_id' => $epdId,
                'gor_epd' => $epd,
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            $userData = $result->current();
            return $userData;
        }
        return null;
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
            case 'new-ssn-removed':
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
