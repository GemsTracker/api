<?php

declare(strict_types=1);


namespace Pulse\Api\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\TableGateway;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

class ActivityActionRepository
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

    protected function createAction($actionName)
    {
        $logActionTable = new TableGateway('gems__log_setup', $this->db);
        $result = $logActionTable->insert([
            'gls_name' => $actionName,
            'gls_on_action' => 1,
            'gls_changed' => new Expression('NOW()'),
            'gls_changed_by' => $this->currentUserRepository->getUserId(),
            'gls_created' => new Expression('NOW()'),
            'gls_created_by' => $this->currentUserRepository->getUserId(),
        ]);

        if ($result) {
            return (int)$logActionTable->getLastInsertValue();
        }
        return null;
    }

    public function getAction($actionName, $create = true)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__log_setup')
            ->columns([
                'gls_id_action',
            ])
            ->where([
                'gls_on_action' => 1,
                'gls_name' => $actionName,
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->current()) {
            $current = $result->current();
            return (int)$current['gls_id_action'];
        }

        if ($create) {
            return $this->createAction($actionName);
        }

        return null;
    }
}
