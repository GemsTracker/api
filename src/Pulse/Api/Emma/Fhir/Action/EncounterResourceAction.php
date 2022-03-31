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
use Pulse\Api\Emma\Fhir\Model\AppointmentModel;
use Pulse\Api\Emma\Fhir\Model\ConditionModel;
use Pulse\Api\Emma\Fhir\Model\EncounterModel;
use Pulse\Api\Emma\Fhir\Repository\AppointmentRepository;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Zalt\Loader\ProjectOverloader;

class EncounterResourceAction extends ResourceActionAbstract
{
    /**
     * @var AppointmentRepository
     */
    protected $appointmentRepository;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;

    public function __construct(EncounterModel$model,
                                AppointmentRepository $appointmentRepository,
                                EpdRepository $epdRepository,
                                CurrentUserRepository $currentUser,
                                EventDispatcher $event,
                                AccesslogRepository $accesslogRepository,
                                ProjectOverloader $loader,
                                UrlHelper $urlHelper,
                                $LegacyDb)
    {
        $this->model = $model;
        $this->appointmentRepository = $appointmentRepository;
        $this->epdRepository = $epdRepository;
        parent::__construct($currentUser, $event, $accesslogRepository, $loader, $urlHelper, $LegacyDb);
    }

    /**
     * Delete a row from the model
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return Response
     */
    public function delete(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $request->getAttribute('id');
        if ($id === null) {
            return new EmptyResponse(404);
        }

        try {
            $changedRows = $this->appointmentRepository->softDeleteAppointmentFromSourceId($id, $this->epdRepository->getEpdName());
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'unknown_error', 'message' => $e->getMessage()], 400);
        }

        if ($changedRows == 0) {
            return new EmptyResponse(404);
        }

        return new EmptyResponse(204);
    }
}
