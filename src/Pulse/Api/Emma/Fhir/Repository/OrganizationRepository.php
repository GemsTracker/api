<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Repository;


use Laminas\Db\Adapter\Adapter;

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
     * @return int get current user ID
     */
    public function getCurrentUserId()
    {
        return $this->currentUser->getUserId();
    }
}
