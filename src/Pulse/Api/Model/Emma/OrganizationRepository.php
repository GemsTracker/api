<?php


namespace Pulse\Api\Model\Emma;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;

class OrganizationRepository
{
    /**
     * @var Adapter
     */
    protected $db;

    protected $localOrganizations;

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    protected function getLocalOrganizations()
    {
        if (!$this->localOrganizations) {
            $sql = new Sql($this->db);
            $select = $sql->select();
            $select->from('gems__organizations')
                ->columns(['gor_id_organization', 'gor_name'])
                ->where(['gor_active' => 1, 'gor_add_respondents' => 1])
                ->order(new Expression('CHAR_LENGTH(gor_name) DESC'));

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
}