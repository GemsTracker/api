<?php

namespace Pulse\Api\Action;

use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Repository\AccesslogRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\IntramedSyncRepository;
use Zalt\Loader\ProjectOverloader;

class FhirAppointmentWithIntramedSynchHandler extends ModelRestController
{
    private IntramedSyncRepository $intramedSyncRepository;

    public function __construct(
        AccesslogRepository $accesslogRepository,
        ProjectOverloader $loader,
        UrlHelper $urlHelper,
        $LegacyDb,
        IntramedSyncRepository $intramedSyncRepository
    )
    {
        parent::__construct($accesslogRepository, $loader, $urlHelper, $LegacyDb);
        $this->intramedSyncRepository = $intramedSyncRepository;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $filters = $this->getListFilter($request);

        $patientId = null;
        if (isset($filters['patient'])) {
            $patientId = $filters['patient'];
        }

        if ($patientId) {
            $this->intramedSyncRepository->checkIntramedSynch($patientId, IntramedSyncRepository::RESOURCE_APPOINTMENT);
        }


        return parent::get($request, $delegate);
    }

}