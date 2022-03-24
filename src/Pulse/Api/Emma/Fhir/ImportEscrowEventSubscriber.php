<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir;
;
use Pulse\Api\Emma\Fhir\Event\SavedModel;
use Pulse\Api\Emma\Fhir\Repository\AppointmentRepository;
use Pulse\Api\Emma\Fhir\Repository\ConditionRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportEscrowLinkRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Copy respondent to correct organization after it is known
 */
class ImportEscrowEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var ImportEscrowLinkRepository
     */
    protected $importEscrowLinkRepository;
    /**
     * @var AppointmentRepository
     */
    protected $appointmentRepository;
    /**
     * @var ConditionRepository
     */
    protected $conditionRepository;

    public function __construct(ImportEscrowLinkRepository $importEscrowLinkRepository, AppointmentRepository $appointmentRepository, ConditionRepository $conditionRepository)
    {
        $this->importEscrowLinkRepository = $importEscrowLinkRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->conditionRepository = $conditionRepository;
    }

    public static function getSubscribedEvents()
    {
        return [

            'model.conditionModel.saved' => [
                ['checkImportEscrowItems'],
            ],
            'model.episodeOfCareModel.saved' => [
                ['checkImportEscrowItems'],
            ],
        ];
    }

    public function checkImportEscrowItems(SavedModel $event)
    {
        $newData = $event->getNewData();

        $savedItemType = $newData['resourceType'];
        $savedItemSourceId = $newData['id'];


        $links = $this->importEscrowLinkRepository->getEscrowLinks($savedItemType, $savedItemSourceId);

        foreach($links as $link) {
            if ($link['gie_target_resource_type'] === 'condition') {
                if ($link['gie_source_resource_type'] === 'appointment') {
                    $episodeOfCareId = $newData['gmco_id_episode_of_care'];
                    if ($episodeOfCareId !== null) {
                        $this->appointmentRepository->addEpisodeOfCareIdToAppointmentSourceId($link['gie_source_id'], $link['gie_source'], $episodeOfCareId);
                    } else {
                        $this->importEscrowLinkRepository->addEscrowLink('episodeOfCare', $newData['episodeOfCareSourceId'], $link['gie_source_resource_type'], $link['gie_source_id']);
                    }
                }
            }
            if ($link['gie_target_resource_type'] === 'episodeOfCare') {
                $episodeOfCareId = $newData['gec_episode_of_care_id'];
                if ($link['gie_source_resource_type'] === 'condition') {
                    $this->conditionRepository->addEpisodeOfCareIdToConditionSourceId($link['gie_source_id'], $link['gie_source'], $episodeOfCareId);
                }
                if ($link['gie_source_resource_type'] === 'appointment') {
                    $this->appointmentRepository->addEpisodeOfCareIdToAppointmentSourceId($link['gie_source_id'], $link['gie_source'], $episodeOfCareId);
                }
            }
            $this->importEscrowLinkRepository->removeEscrowLink($link['gie_id_link']);
        }
    }

}
