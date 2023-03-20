<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir;
;
use Pulse\Api\Emma\Fhir\Event\SavedModel;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportDbLogRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportLogRepository;
use Pulse\Api\Repository\RespondentRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Copy respondent to correct organization after it is known
 */
class CheckRespondentOrganizationEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var RespondentRepository
     */
    protected $respondentRepository;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;
    /**
     * @var ImportLogRepository
     */
    protected $importLogRepository;
    /**
     * @var ImportDbLogRepository
     */
    protected $importDbLogRepository;
    /**
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;

    public function __construct(RespondentRepository $respondentRepository,
                                EpdRepository $epdRepository)
    {
        $this->respondentRepository = $respondentRepository;
        $this->epdRepository = $epdRepository;
    }

    public static function getSubscribedEvents()
    {
        return [

            'model.appointmentModel.saved' => [
                ['checkRespondentOrganization', 50],
            ],
            'model.episodeOfCareModel.saved' => [
                ['checkRespondentOrganization', 50],
            ],
        ];
    }

    public function checkRespondentOrganization(SavedModel $event)
    {
        $newData = $event->getNewData();
        $respondentId = null;
        $organizationId = null;
        $locationId = null;
        if (isset($newData['gap_id_user'], $newData['gap_id_organization'])) {
            $respondentId = $newData['gap_id_user'];
            $organizationId = $newData['gap_id_organization'];
            $locationId = $newData['gap_id_location'];
        }
        if (isset($newData['gec_id_user'], $newData['gec_id_organization'])) {
            $respondentId = $newData['gec_id_user'];
            $organizationId = $newData['gec_id_organization'];
            $locationId = $newData['locationId'];
        }

        if ($respondentId !== null && $organizationId !== null) {
            if ($this->respondentRepository->respondentExistsInOrganization($respondentId, $organizationId)) {
                return;
            }
            $this->respondentRepository->copyRespondentToOrganization($respondentId, $organizationId, $this->epdRepository->getEpdName(), $locationId);

        }
    }

}
