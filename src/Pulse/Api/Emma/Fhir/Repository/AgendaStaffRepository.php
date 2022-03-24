<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;

class AgendaStaffRepository
{
    /**
     * @var Adapter
     */
    protected $db;
    /**
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;

    public function __construct(Adapter $db, CurrentUserRepository $currentUserRepository)
    {
        $this->db = $db;
        $this->currentUserRepository = $currentUserRepository;
    }

    protected function addSourceToStaff($staffId, $source, $sourceId)
    {
        $agendaStaffTable = new TableGateway('gems__agenda_staff', $this->db);
        $agendaStaffTable->update([
            'gas_source' => $source,
            'gas_id_in_source' => $sourceId,
        ], [
            'gas_id_staff' => $staffId,
        ]);
    }

    /**
     * Create a new agenda staff
     *
     * @param string $name Staff member name
     * @param int $organizationId Organization ID
     * @return int|null Last insert value
     */
    public function createStaff(string $name, int $organizationId): ?int
    {
        if (strlen($name) > 250) {
            $name = substr_replace($name, '...', 247);
        }

        $agendaStaffTable = new TableGateway('gems__agenda_staff', $this->db);
        $result = $agendaStaffTable->insert([
            'gas_name' => $name,
            'gas_id_organization' => $organizationId,
            'gas_match_to' => $name,
            'gas_changed' => new Expression('NOW()'),
            'gas_changed_by' => $this->currentUserRepository->getUserId(),
            'gas_created' => new Expression('NOW()'),
            'gas_created_by' => $this->currentUserRepository->getUserId(),
        ]);

        if ($result) {
            return (int)$agendaStaffTable->getLastInsertValue();
        }
        return null;
    }

    /**
     * @param $name string Staff member name
     * @param $organizationId int Organization ID
     * @param $create bool Should the resource be created if it is not known
     * @return int|null Staff member ID that was matched or null
     */
    public function matchStaff($name, $organizationId, $create=true)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__agenda_staff')
            ->columns(['gas_id_staff']);

        $select->where
            ->nest()
                ->equalTo('gas_match_to', $name)
                ->or
                ->like('gas_match_to', '%|'.$name.'|%')
                ->or
                ->like('gas_match_to', $name.'|%')
                ->or
                ->like('gas_match_to', '%|'.$name)
            ->unnest()
            ->and
            ->equalTo('gas_active', 1)
            ->and
            ->equalTo('gas_id_organization', $organizationId);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            $match = $result->current();
            return (int)$match['gas_id_staff'];
        }

        if ($create) {
            return $this->createStaff($name, $organizationId);
        }
        return null;
    }

    /**
     * @param $name string Staff member name
     * @param $sourceId string Source ID
     * @param $source string Source name
     * @param $organizationId int Organization ID
     * @param $create bool Should the resource be created if it is not known
     * @return int|null Staff member ID that was matched or null
     */
    public function matchStaffByNameOrSourceId($name, $sourceId, $source, $organizationId, $create=true)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__agenda_staff')
            ->columns(['gas_id_staff']);

        $select->where
            ->nest()
                ->nest()
                    ->equalTo('gas_id_in_source', $sourceId)
                    ->and
                    ->equalTo('gas_source', $source)
                ->unnest()
                ->or
                ->equalTo('gas_match_to', $name)
                ->or
                ->like('gas_match_to', '%|'.$name.'|%')
                ->or
                ->like('gas_match_to', $name.'|%')
                ->or
                ->like('gas_match_to', '%|'.$name)
            ->unnest()
            ->and
            ->equalTo('gas_active', 1)
            ->and
            ->equalTo('gas_id_organization', $organizationId);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            $match = $result->current();

            if (!isset($match['gas_id_in_source'])) {
                $this->addSourceToStaff($match['gas_id_staff'], $source, $sourceId);
            }

            return (int)$match['gas_id_staff'];
        }

        if ($create) {
            return $this->createStaff($name, $organizationId);
        }
        return null;
    }
}
