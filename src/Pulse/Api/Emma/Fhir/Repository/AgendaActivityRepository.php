<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;

class AgendaActivityRepository
{
    /**
     * @var CurrentUserRepository UserId
     */
    protected $currentUserRepository;

    /**
     * @var Adapter
     */
    protected $db;

    public function __construct(Adapter $db, CurrentUserRepository $currentUserRepository)
    {
        $this->db = $db;
        $this->currentUserRepository = $currentUserRepository;
    }

    public function changeActivityOrganization($oldActivityId, $newOrganizationId)
    {
        $activityName = $this->getActivityNameById($oldActivityId);
        return $this->matchActivity($activityName, $newOrganizationId, true);
    }

    /**
     * Create a new activity
     *
     * @param string $name Activity name
     * @param int $organizationId Organization ID
     * @return int|null Last insert value
     */
    public function createActivity($name, $organizationId)
    {
        if (strlen($name) > 250) {
            $name = substr_replace($name, '...', 247);
        }

        $locationTable = new TableGateway('gems__agenda_activities', $this->db);
        $result = $locationTable->insert([
            'gaa_name' => $name,
            'gaa_id_organization' => $organizationId,
            'gaa_match_to' => $name,
            'gaa_changed' => new Expression('NOW()'),
            'gaa_changed_by' => $this->currentUserRepository->getUserId(),
            'gaa_created' => new Expression('NOW()'),
            'gaa_created_by' => $this->currentUserRepository->getUserId(),
        ]);

        if ($result) {
            return (int)$locationTable->getLastInsertValue();
        }
        return null;
    }

    public function getActivityNameById($activityId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__agenda_activities')
            ->columns(['gaa_name'])
            ->where([
                'gaa_id_activity' => $activityId,
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid()) {
            $match = $result->current();
            return $match['gaa_name'];
        }
        return null;
    }

    /**
     * Match an activity to one in the database
     *
     * @param $name string Activity name
     * @param $organizationId int Organization ID
     * @param $create bool Should the resource be created if it is not known
     * @return int|null activity ID that was matched or null
     */
    public function matchActivity($name, $organizationId, $create = true)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__agenda_activities')
            ->columns(['gaa_id_activity']);

        $select->where
            ->nest()
                ->equalTo('gaa_match_to', $name)
                ->or
                ->like('gaa_match_to', '%|'.$name.'|%')
                ->or
                ->like('gaa_match_to', $name.'|%')
                ->or
                ->like('gaa_match_to', '%|'.$name)
            ->unnest()
            ->and
            ->equalTo('gaa_active', 1)
            ->and
            ->equalTo('gaa_id_organization', $organizationId);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            $match = $result->current();
            return (int)$match['gaa_id_activity'];
        }

        if ($create) {
            return $this->createActivity($name, $organizationId);
        }

        return null;
    }
}
