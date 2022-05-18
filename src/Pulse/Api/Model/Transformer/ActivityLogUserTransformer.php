<?php

declare(strict_types=1);


namespace Pulse\Api\Model\Transformer;


use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;

class ActivityLogUserTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;

    public function __construct(CurrentUserRepository $currentUserRepository)
    {
        $this->currentUserRepository = $currentUserRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (!isset($row['gla_by'])) {
            $row['gla_by'] = $this->currentUserRepository->getUserId();
        }

        if (!isset($row['gla_role'])) {
            $row['gla_role'] = $this->currentUserRepository->getUserRole();
            if ($row['gla_role'] === null) {
                $row['gla_role'] = 'unknown';
            }
        }

        return $row;
    }
}
