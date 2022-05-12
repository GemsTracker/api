<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\TableGateway\TableGateway;

class ImportEscrowLinkRepository
{
    /**
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;

    /**
     * @var Adapter
     */
    protected $db;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(Adapter $db, CurrentUserRepository $currentUserRepository, EpdRepository $epdRepository)
    {
        $this->db = $db;
        $this->currentUserRepository = $currentUserRepository;
        $this->epdRepository = $epdRepository;
    }

    /**
     * @param $targetType string resource type that should be linked later
     * @param $targetId int id of the resource that should be linked later
     * @param $sourceType string type of the existing resource
     * @param $sourceId int id of the existing resource
     * @return int
     */
    public function addEscrowLink($targetType, $targetId, $sourceType, $sourceId)
    {
        $table = $this->getTableGateway();
        $data = [
            'gie_source' => $this->epdRepository->getEpdName(),
            'gie_target_resource_type' => $targetType,
            'gie_target_id' => $targetId,
            'gie_source_resource_type' => $sourceType,
            'gie_source_id' => $sourceId,

            'gie_changed' => new Expression('NOW()'),
            'gie_changed_by' => $this->currentUserRepository->getUserId(),
            'gie_created' => new Expression('NOW()'),
            'gie_created_by' => $this->currentUserRepository->getUserId(),
        ];

        return $table->insert($data);
    }

    /**
     * get the current import links in escrow for a specific resource
     *
     * @param $targetType string resource type
     * @param $targetId int resource id
     * @return array
     */
    public function getEscrowLinks($targetType, $targetId)
    {
        $table = $this->getTableGateway();
        $result = $table->select([
            'gie_source' => $this->epdRepository->getEpdName(),
            'gie_target_resource_type' => $targetType,
            'gie_target_id' => $targetId,
        ]);

        return iterator_to_array($result);
    }

    /**
     * Get the import escrow link table gateway
     *
     * @return TableGateway
     */
    protected function getTableGateway()
    {
        return new TableGateway('gems__import_escrow_links', $this->db);
    }



    /**
     * Remove an escrow link
     *
     * @param $id int link id
     * @return int number of effected rows
     */
    public function removeEscrowLink($id)
    {
        $table = $this->getTableGateway();
        return $table->delete(['gie_id_link' => $id]);
    }
}
