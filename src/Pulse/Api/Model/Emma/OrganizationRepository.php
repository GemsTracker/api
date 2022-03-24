<?php


namespace Pulse\Api\Model\Emma;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;

class OrganizationRepository
{
    /**
     * @var int UserId
     */
    protected $currentUserId;

    /**
     * @var Adapter
     */
    protected $db;

    protected $localOrganizations;

    public function __construct(Adapter $db, CurrentUserRepository $currentUserRepository)
    {
        $this->db = $db;
        $this->currentUserId = $currentUserRepository->getUserId();
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
            'glo_changed_by' => $this->currentUserId,
            'glo_created' => new Expression('NOW()'),
            'glo_created_by' => $this->currentUserId,
        ]);

        if ($result) {
            return (int)$locationTable->getLastInsertValue();
        }
        return null;
    }

    public function getCurrentUserId()
    {
        return $this->currentUserId;
    }

    protected function getLocalOrganizations()
    {
        if (!$this->localOrganizations) {
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

            $this->localOrganizations = $filteredOrganizations;
        }

        return $this->localOrganizations;
    }

    public function getLocationFromOrganizationName($organizationAndLocationString)
    {
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

    public function matchLocation($name, $organizationId, $create = true)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__locations')
            ->columns(['glo_id_location']);

        $select->where
            ->nest()
                ->equalTo('glo_match_to', $name)
                ->or
                ->like('glo_match_to', '%|'.$name.'|%')
                ->or
                ->like('glo_match_to', $name.'|%')
                ->or
                ->like('glo_match_to', '%|'.$name)
            ->unnest()
            ->and
            ->equalTo('glo_active', 1)
            ->and
            ->equalTo('glo_organizations', ':'.$organizationId.':');

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            $match = $result->current();
            return (int)$match['glo_id_location'];
        }

        if ($create) {
            $this->createLocation($name, $organizationId);
        }
    }
}
