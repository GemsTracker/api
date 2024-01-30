<?php

namespace Pulse\Api\Repository;

use Gems\Rest\Db\ResultFetcher;
use Gems\Rest\Legacy\CurrentUserRepository;
use Laminas\Db\Adapter\Adapter;
use Pulse\Intramed\IntramedClient;
use Zalt\Loader\ProjectOverloader;

class IntramedSyncRepository
{
    const RESOURCE_APPOINTMENT = 'appointment';
    const RESOURCE_EPISODE = 'episode';
    const RESOURCE_PATIENT = 'patient';

    protected CurrentUserRepository $currentUserRepository;

    /**
     * @var IntramedClient|null
     */
    protected $intramedClient = null;
    protected array $intramedOrganizationIds = [
        88,
    ];

    protected ProjectOverloader $overloader;

    protected ResultFetcher $resultFetcher;

    protected $syncWaitPeriod = 'PT4H';

    public function __construct(
        ResultFetcher $resultFetcher,
        CurrentUserRepository $currentUserRepository,
        ProjectOverloader $overloader,
        \Gems_Loader $loader
    )
    {
        $this->resultFetcher = $resultFetcher;
        $this->overloader = $overloader;
        $this->currentUserRepository = $currentUserRepository;
    }

    public function checkIntramedSynch($patientNrCombinations, $resourceType)
    {
        if (!$patientNrCombinations) {
            throw new \Exception('No valid patientNr supplied');
        }

        if (!is_array($patientNrCombinations)) {
            $patientNrCombinations = [$patientNrCombinations];
        }

        foreach ($patientNrCombinations as $patientNrCombination) {
            list($patientNr, $organizationId) = explode('@', $patientNrCombination);

            if (!in_array($organizationId, $this->intramedOrganizationIds)) {
                return null;
            }

            if (!$this->shouldDoFreshSync($patientNr, $organizationId, $resourceType)) {
                return null;
            }

            // Do actual sync!
            $intramedClient = $this->getIntramedClient();
            $intramedClient->updatePatient($patientNr, $organizationId, false, true);

        }
    }

    protected function getIntramedClient(): IntramedClient
    {
        if (!$this->intramedClient) {
            $this->loadIntramedSettings();
            $this->intramedClient = $this->overloader->create(IntramedClient::class, $this->currentUserRepository->getCurrentUser());
        }
        return $this->intramedClient;
    }

    protected function getLastRefresh(string $patientNr, int $organizationId, string $resourceType)
    {
        $select = $this->resultFetcher->getSelect('pulse__intramed_last_sync');
        $select->columns(['pils_created'])
            ->where([
                'pils_patient_nr' => $patientNr,
                'pils_id_organization' => $organizationId,
                'pils_resource_type' => $resourceType,
            ])
            ->order('pils_created DESC')
            ->limit(1);

        $result = $this->resultFetcher->fetchOne($select);
        if ($result) {
            return new \DateTimeImmutable($result);
        }
        return null;
    }

    protected function loadIntramedSettings(): void
    {
        require(GEMS_ROOT_DIR . '/config/intramedSettings.php');
    }


    public function shouldDoFreshSync(string $patientNr, int $organizationId, string $resourceType): bool
    {
        // get last sync dateTime
        $lastRefreshDate = $this->getLastRefresh($patientNr, $organizationId, $resourceType);
        if ($lastRefreshDate === null) {
            return true;
        }
        $compareDateTime = (new \DateTimeImmutable())->sub(new \DateInterval($this->syncWaitPeriod));

        return $lastRefreshDate <= $compareDateTime;
    }
}