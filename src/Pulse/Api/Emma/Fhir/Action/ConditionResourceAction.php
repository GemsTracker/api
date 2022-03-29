<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Action;


use Gems\Event\EventDispatcher;
use Gems\Rest\Repository\AccesslogRepository;
use Mezzio\Helper\UrlHelper;
use Pulse\Api\Emma\Fhir\Model\AppointmentModel;
use Pulse\Api\Emma\Fhir\Model\ConditionModel;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Zalt\Loader\ProjectOverloader;

class ConditionResourceAction extends ResourceActionAbstract
{
    public function __construct(ConditionModel $model, CurrentUserRepository $currentUser, EventDispatcher $event, AccesslogRepository $accesslogRepository, ProjectOverloader $loader, UrlHelper $urlHelper, $LegacyDb)
    {
        $this->model = $model;
        parent::__construct($currentUser, $event, $accesslogRepository, $loader, $urlHelper, $LegacyDb);
    }
}