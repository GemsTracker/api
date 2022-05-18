<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;
use Pulse\Api\Emma\Fhir\EscrowOrganization;

class IntakeAnaesthesiaLinkRepository
{
    /**
     * @var Adapter
     */
    protected $db;

    protected $appointmentMaxAge = 'P90D';

    protected $tokenAgeInterval = 'P6M';
    /**
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;
    /**
     * @var ImportDbLogRepository
     */
    protected $importDbLogRepository;
    /**
     * @var EscrowOrganizationRepository
     */
    protected $escrowOrganizationRepository;


    public function __construct(Adapter $db, CurrentUserRepository $currentUserRepository, ImportDbLogRepository $importDbLogRepository, EscrowOrganizationRepository $escrowOrganizationRepository)
    {
        $this->db = $db;
        $this->currentUserRepository = $currentUserRepository;
        $this->importDbLogRepository = $importDbLogRepository;
        $this->escrowOrganizationRepository = $escrowOrganizationRepository;
    }

    public function checkAppointmentLink(array $appointmentData)
    {
        // No need to check if no activity is known
        if (!isset($appointmentData['gap_id_activity'])) {
            return false;
        }
        // No need to check if still in escrow organization
        if ($appointmentData['gap_id_organization'] === $this->escrowOrganizationRepository->getId()) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $appointmentStartTime = null;
        if ($appointmentData['gap_admission_time'] instanceof \MUtil_Date) {
            $appointmentStartTime = new \DateTimeImmutable($appointmentData['gap_admission_time']->toString('yyyy-MM-dd HH:mm:ss'));
        } elseif (is_string($appointmentData['gap_admission_time'])) {
            $appointmentStartTime = new \DateTimeImmutable($appointmentData['gap_admission_time']);
        }

        // No need to check appointments older than max age
        if ($appointmentStartTime->add(new \DateInterval($this->appointmentMaxAge)) < $now) {
            return false;
        }

        $linkData = $this->getActivityAnaesthesiologyLink($appointmentData['gap_id_activity']);
        // No need to continue if no link is found
        if ($linkData === null || !isset($linkData['pa2a_code'])) {
            return false;
        }

        $currentLink = $this->getCurrentLink($appointmentData['gap_id_appointment']);
        // No changes needed if current link is still correct!
        if ($currentLink !== null && $currentLink['grc_success'] == 1 && $currentLink['gsu_active'] == 1) {
            return false;
        }

        $token = $this->getLinkToken($appointmentData['gap_id_user'], $appointmentData['gap_id_organization'], $linkData['pa2a_code'], $appointmentStartTime);

        $this->linkAppointmentToken($appointmentData, $linkData, $currentLink, $token);

        return true;
    }

    protected function linkAppointmentToken($appointmentData, $linkData, $currentLink, $token)
    {
        $now = new \DateTimeImmutable();
        $newLinkValues = [
            'pat_changed' => $now->format('Y-m-d H:i:s'),
            'pat_changed_by' => $this->currentUserRepository->getUserId(),
            'pat_code' => $linkData['pa2a_code'],
            'pat_intake' => $linkData['pa2a_intake'],
            'pat_aneasthesia' => $linkData['pa2a_aneasthesia'],
        ];

        if ($token !== null) {
            $newLinkValues['pat_id_token'] = $token['gto_id_token'];
        } else {
            $newLinkValues['pat_id_token'] = null;
        }

        $table = new TableGateway('pulse__anaesthesia_tokens', $this->db);
        $created = true;
        if ($currentLink === null) {
            // Insert
            $newLinkValues['pat_id_appointment'] = $appointmentData['gap_id_appointment'];
            $newLinkValues['pat_created'] = $now->format('Y-m-d H:i:s');
            $newLinkValues['pat_created_by'] = $this->currentUserRepository->getUserId();

            $table->insert($newLinkValues);
        } else {
            // Update
            $created = false;
            $table->update($newLinkValues, ['pat_id_appointment' => $appointmentData['gap_id_appointment']]);
        }

        $this->logAppointmentActivity($appointmentData['gap_id_appointment'],
            $appointmentData['gap_id_user'],
            $appointmentData['gap_id_organization'],
            $created,
            $newLinkValues['pat_id_token']
        );

        return true;
    }

    public function getActivityAnaesthesiologyLink($activityId)
    {
        $sql =  new Sql($this->db);
        $select = $sql->select('pulse__activity2anaesthesiology');
        $select
            ->join('gems__agenda_activities', new Expression('`gaa_name` LIKE `pa2a_activity`'), [])
            ->where([
                'gaa_id_activity' => $activityId,
                'pa2a_active' => 1
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            return $result->current();
        }

        return null;
    }

    public function getLinkToken($respondentId, $organizationId, $surveyCode, \DateTimeImmutable $appointmentStartTime)
    {
        $minDateTime = $appointmentStartTime->sub(new \DateInterval($this->tokenAgeInterval));

        $sql =  new Sql($this->db);
        $select = $sql->select('gems__tokens');
        $select->join('gems__surveys', 'gto_id_survey = gsu_id_survey', ['gsu_code'])
            ->join('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', ['grc_success'])
            ->where([
                'grc_success' => 1,
                'gsu_active' => 1,
                'gto_id_respondent' => $respondentId,
                'gto_id_organization' => $organizationId,
                'gsu_code' => $surveyCode,
            ]);
        $select->where->greaterThanOrEqualTo(new Expression('COALESCE(gto_completion_time, gto_valid_from)'), $minDateTime->format('Y-m-d H:i:s'))
            ->nest()
                ->isNotNull('gto_completion_time')
                ->or
                ->lessThan('gto_valid_from', $appointmentStartTime->format('Y-m-d H:i:s'))
            ->unnest()
            ->nest()
                ->isNotNull('gto_completion_time')
                ->or
                ->isNull('gto_valid_until')
                ->or
                ->greaterThan('gto_valid_until', new Expression('CURRENT_TIMESTAMP'))
            ->unnest();
        $select->order('gto_valid_from DESC');

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            return $result->current();
        }
        return null;
    }

    public function getCurrentLink($appointmentId)
    {
        $sql =  new Sql($this->db);
        $select = $sql->select('pulse__anaesthesia_tokens');
        $select
            ->join('gems__tokens', 'gto_id_token = pat_id_token')
            ->join('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', ['grc_success'])
            ->join('gems__surveys', 'gto_id_survey = gsu_id_survey', ['gsu_active'])
            ->where([
                'pat_id_appointment' => $appointmentId,
                'grc_success' => 1,
                'gsu_active' => 1,
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            return $result->current();
        }
        return null;
    }

    protected function logAppointmentActivity($appointmentId, $respondentId, $organizationId, $created, $tokenId)
    {
        $description = $created ? 'Created' : 'Changed';
        if (! $tokenId) {
            $description .= ' empty';
        }
        $description .= ' appointment link width' . $organizationId;
        if ($tokenId) {
            $description .= ' on ' . $tokenId;
        }

        $now = new \DateTimeImmutable();

        $data = [
            'pls_appointment_id' => $appointmentId,
            'pls_id_respondent' => $respondentId,
            'pls_patient_nr' => '',
            'pls_id_organization' => $organizationId,
            'pls_is_change' => (int)!$created,
            'pls_description' => $description,
            'pls_created' => $now->format('Y-m-d H:i:s'),
        ];

        $this->importDbLogRepository->logEpdChange($data);
    }
}
