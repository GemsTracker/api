<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;

class OrganizationRepository extends \Pulse\Api\Model\Emma\OrganizationRepository
{
    /**
     * @var CurrentUserRepository
     */
    protected $currentUser;

    public function __construct(Adapter $db, CurrentUserRepository $currentUser)
    {
        parent::__construct($db, $currentUser);
        $this->currentUser = $currentUser;
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

    /**
     * @return int get current user ID
     */
    public function getCurrentUserId()
    {
        return $this->currentUser->getUserId();
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
