<?php


namespace Pulse\Api\Model\Emma;

use Gems\Rest\Cache\Psr6CacheHelpers;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Psr\Cache\CacheItemPoolInterface;

class OrganizationRepository
{
    use Psr6CacheHelpers;

    /**
     * @var int UserId
     */
    protected $currentUserId;

    /**
     * @var Adapter
     */
    protected $db;

    protected $localOrganizations;

    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    protected $organizationCacheItemKey = 'api.pulse.emma.fhir.organizations';

    public function __construct(Adapter $db, CacheItemPoolInterface $cache)
    {
        $this->db = $db;
        $this->cache = $cache;
    }

    public function getCurrentUserId()
    {
        return $this->currentUserId;
    }

    protected function getLocalOrganizations()
    {
        if (!$this->localOrganizations) {
            if ($localOrganizations = $this->getCacheItem($this->organizationCacheItemKey)) {
                $this->localOrganizations = $localOrganizations;
                return $this->localOrganizations;
            }

            $sql = new Sql($this->db);
            $select = $sql->select();
            $select->from('gems__organizations')
                ->columns(['gor_id_organization', 'gor_name'])
                ->where(['gor_active' => 1, 'gor_add_respondents' => 1])
                ->order(new Expression('LENGTH(gor_name) DESC'));

            $statement = $sql->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            $organizations = iterator_to_array($result);
            $filteredOrganizations = [];
            foreach ($organizations as $organization) {
                $filteredOrganizations[$organization['gor_id_organization']] = $organization['gor_name'];
            }

            $this->setCacheItem($this->organizationCacheItemKey, $filteredOrganizations, ['organizations']);

            $this->localOrganizations = $filteredOrganizations;
        }

        return $this->localOrganizations;
    }

    public function getLocationFromOrganizationName($organizationAndLocationString)
    {
        $organizationAndLocationString = $this->tempTranslateOrganizationName($organizationAndLocationString);
        
        $localOrganizations = $this->getLocalOrganizations();
        $location = trim(str_replace($localOrganizations, '', $organizationAndLocationString));

        if (empty($location)) {
            return null;
        }

        return $location;
    }

    public function getOrganizationTranslations($organizations)
    {
        $organizationIds = [];
        foreach($organizations as $organization) {
            if ($organizationId = $this->getOrganizationId($organization)) {
                $organizationIds[$organizationId] = $organization;
            }
        }

        return $organizationIds;
    }

    public function getOrganizationId($organizationName)
    {
        $organizationName = $this->tempTranslateOrganizationName($organizationName);

        $localOrganizations = $this->getLocalOrganizations();

        $organizationCompare = strtolower(trim($organizationName));
        foreach($localOrganizations as $organizationId => $localOrganization) {
            $localOrganizationCompare = strtolower(trim($localOrganization));
            if (strpos($organizationCompare, $localOrganizationCompare) !== false) {
                return $organizationId;
            }
        }

        return null;
    }

    protected function tempTranslateOrganizationName($organizationName)
    {
        $tempOrganizationTranslations = [
            'Annatommie mc' => 'Xpert Clinics Orthopedie',
        ];
        foreach ($tempOrganizationTranslations as $oldName => $newName) {
            if (strpos($organizationName, $oldName) === 0) {
                $organizationName = str_replace($oldName, $newName, $organizationName);
            }
        }

        return $organizationName;
    }
}
