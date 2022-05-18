<?php

declare(strict_types=1);


namespace Pulse\Api\Model\Transformer;


use Gems\Rest\Model\ModelException;
use Pulse\Api\Repository\ActivityActionRepository;

class ActivityLogActionTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var ActivityActionRepository
     */
    protected $activityActionRepository;

    public function __construct(ActivityActionRepository $activityActionRepository)
    {
        $this->activityActionRepository = $activityActionRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (isset($row['actionName'])) {
            $actionId = $this->activityActionRepository->getAction($row['actionName']);
            if ($actionId === null) {
                throw new ModelException('Action could not be found');
            }
            $row['gla_action'] = $actionId;
        }

        return $row;
    }
}
