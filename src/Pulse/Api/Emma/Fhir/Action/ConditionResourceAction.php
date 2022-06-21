<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Action;


use Gems\Event\EventDispatcher;
use Gems\Rest\Repository\AccesslogRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Emma\Fhir\Event\DeleteResourceEvent;
use Pulse\Api\Emma\Fhir\Model\AppointmentModel;
use Pulse\Api\Emma\Fhir\Model\ConditionModel;
use Pulse\Api\Emma\Fhir\Repository\ConditionRepository;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Zalt\Loader\ProjectOverloader;

class ConditionResourceAction extends ResourceActionAbstract
{
    /**
     * @var ConditionRepository
     */
    protected $conditionRepository;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(ConditionModel $model,
                                ConditionRepository $conditionRepository,
                                EpdRepository $epdRepository,
                                CurrentUserRepository $currentUser,
                                EventDispatcher $event,
                                AccesslogRepository $accesslogRepository,
                                ProjectOverloader $loader,
                                UrlHelper $urlHelper,
                                $LegacyDb)
    {
        $this->model = $model;
        $this->conditionRepository = $conditionRepository;
        $this->epdRepository = $epdRepository;
        parent::__construct($currentUser, $event, $accesslogRepository, $loader, $urlHelper, $LegacyDb);
    }

    protected function addRespondentInfoToEvent(DeleteResourceEvent $event, $sourceId)
    {
        $condition = $this->conditionRepository->getConditionBySourceId($sourceId);
        if ($condition) {
            $event->setRespondentId($condition['gmco_id_user']);
        }
    }

    public function deleteResourceFromSourceId($sourceId)
    {
        return $this->conditionRepository->softDeleteConditionFromSourceId($sourceId, $this->epdRepository->getEpdName());
    }

    protected function getRespondentIdFromSourceId($sourceId)
    {
        $condition = $this->conditionRepository->getConditionBySourceId($sourceId, $this->epdRepository->getEpdName());
        if ($condition) {
            return $condition['gmco_id_user'];
        }

        return null;
    }
}
