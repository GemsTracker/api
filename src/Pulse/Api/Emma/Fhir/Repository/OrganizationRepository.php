<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Cache\CacheItemPoolInterface;

class OrganizationRepository extends \Pulse\Api\Model\Emma\OrganizationRepository
{
    /**
     * @var CurrentUserRepository
     */
    protected $currentUser;

    /**
     * @var array List of Locations and matches
     */
    protected $locations;

    protected $locationCacheItemKey = 'api.pulse.emma.fhir.locations';

    public function __construct(Adapter $db, CacheItemPoolInterface $cache, CurrentUserRepository $currentUser)
    {
        parent::__construct($db, $cache);
        $this->currentUser = $currentUser;
    }

    public function addOrganizationToLocation($location, $organizationId)
    {
        $organizations = $location['glo_organizations'] . $organizationId . ':';

        $locationTable = new TableGateway('gems__locations', $this->db);
        $result = $locationTable->update([
            'glo_organizations' => $organizations,
        ], [
            'glo_id_location' => $location['glo_id_location'],
        ]);

        return (bool)$result;
    }

    /**
     * @param string $name Location name
     * @param int $organizationId Organization ID
     * @return int|null Last insert value
     */
    public function createLocation($name, $organizationId)
    {
        if (strlen($name) > 250) {
            $name = substr_replace($name, '...', 247);
        }

        $locationTable = new TableGateway('gems__locations', $this->db);
        $result = $locationTable->insert([
            'glo_name' => $name,
            'glo_organizations' => ':'.$organizationId.':',
            'glo_match_to' => $name,
            'glo_changed' => new Expression('NOW()'),
            'glo_changed_by' => $this->currentUser->getUserId(),
            'glo_created' => new Expression('NOW()'),
            'glo_created_by' => $this->currentUser->getUserId(),
        ]);

        if ($result) {
            return (int)$locationTable->getLastInsertValue();
        }
        return null;
    }

    /**
     * @return int get current user ID
     */
    public function getCurrentUserId()
    {
        return $this->currentUser->getUserId();
    }

    protected function getLocations()
    {
        if (!$this->locations) {
            if ($locations = $this->getCacheItem($this->locationCacheItemKey)) {
                $this->locations = $locations;
                return $this->locations;
            }

            $sql = new Sql($this->db);
            $select = $sql->select();
            $select->from('gems__locations')
                ->columns(['glo_id_location', 'glo_match_to', 'glo_organizations'])
                ->where(['glo_active' => 1]);

            $statement = $sql->prepareStatementForSqlObject($select);
            $result = $statement->execute();
            $locationOptions = iterator_to_array($result);
            $sortedLocations = [];

            foreach ($locationOptions as $row) {
                if ($row['glo_match_to'] !== null) {
                    foreach (explode('|', $row['glo_match_to']) as $match) {
                        foreach (explode(':', trim($row['glo_organizations'], ':')) as $subOrg) {
                            $sortedLocations[$match][$subOrg] = $row;
                        }
                    }
                }
            }
            $this->setCacheItem($this->locationCacheItemKey, $sortedLocations, ['locations']);
            $this->locations = $sortedLocations;
        }
        return $this->locations;
    }

    public function matchLocation($name, $organizationId, $create = true)
    {
        $locations = $this->getLocations();

        if (isset($locations[$name])) {
            if (isset($locations[$name][$organizationId])) {
                return (int)$locations[$name][$organizationId]['glo_id_location'];
            }
            $location = (int)reset($locations[$name]);
            $this->addOrganizationToLocation($location, $organizationId);
            return $location['glo_id_location'];
        }

        if ($create) {
            $this->createLocation($name, $organizationId);
        }
    }
}
