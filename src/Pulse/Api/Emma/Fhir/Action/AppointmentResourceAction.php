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
use Pulse\Api\Emma\Fhir\Repository\AppointmentRepository;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Zalt\Loader\ProjectOverloader;

class AppointmentResourceAction extends ResourceActionAbstract
{
    /**
     * @var AppointmentRepository
     */
    protected $appointmentRepository;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(AppointmentModel $model,
                                AppointmentRepository $appointmentRepository,
                                EpdRepository $epdRepository,
                                CurrentUserRepository $currentUser,
                                EventDispatcher $event,
                                AccesslogRepository $accesslogRepository,
                                ProjectOverloader $loader,
                                UrlHelper $urlHelper, $LegacyDb)
    {
        $this->model = $model;
        $this->appointmentRepository = $appointmentRepository;
        $this->epdRepository = $epdRepository;
        parent::__construct($currentUser, $event, $accesslogRepository, $loader, $urlHelper, $LegacyDb);
    }

    public function deleteResourceFromSourceId($sourceId)
    {
        return $this->appointmentRepository->softDeleteAppointmentFromSourceId($sourceId, $this->epdRepository->getEpdName());
    }
}
