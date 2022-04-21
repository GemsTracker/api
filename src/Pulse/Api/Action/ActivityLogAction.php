<?php

declare(strict_types=1);


namespace Pulse\Api\Action;


use Gems\Event\EventDispatcher;
use Gems\Rest\Repository\AccesslogRepository;
use Mezzio\Helper\UrlHelper;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Model\ActivityLogModel;
use Pulse\Api\Repository\RequestRepository;
use Zalt\Loader\ProjectOverloader;

class ActivityLogAction extends ConstructorModelRestActionAbstract
{
    public function __construct(ActivityLogModel $model, EventDispatcher $eventDispatcher, CurrentUserRepository $currentUserRepository, RequestRepository $requestRepository, AccesslogRepository $accesslogRepository, ProjectOverloader $loader, UrlHelper $urlHelper, $LegacyDb)
    {
        parent::__construct($model, $eventDispatcher, $currentUserRepository, $requestRepository, $accesslogRepository, $loader, $urlHelper, $LegacyDb);
    }
}
