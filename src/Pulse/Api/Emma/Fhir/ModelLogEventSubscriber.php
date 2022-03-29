<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir;


use Gems\Rest\Model\ModelValidationException;
use Pulse\Api\Emma\Fhir\Event\ModelImport;
use Pulse\Api\Emma\Fhir\Event\SavedModel;
use Pulse\Api\Emma\Fhir\Event\SaveFailedModel;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportDbLogRepository;
use Pulse\Api\Emma\Fhir\Repository\ImportLogRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Model Events that trigger logging on both files and db
 */
class ModelLogEventSubscriber implements EventSubscriberInterface
{
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

    public function __construct(EpdRepository $epdRepository,
                                ImportLogRepository $importLogRepository,
                                ImportDbLogRepository $importDbLogRepository,
                                CurrentUserRepository $currentUserRepository)
    {
        $this->epdRepository = $epdRepository;
        $this->importLogRepository = $importLogRepository;
        $this->importDbLogRepository = $importDbLogRepository;
        $this->currentUserRepository = $currentUserRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            'emma.import.start' => [
                ['logFileImportStart', -10],
            ],
            'model.appointmentModel.saved' => [
                ['logFileImportSaved', -10],
                ['logDbImportSaved', -10],
            ],
            'model.conditionModel.saved' => [
                ['logFileImportSaved', -10],
                ['logDbImportSaved', -10],
            ],
            'model.encounterModel.saved' => [
                ['logFileImportSaved', -10],
                ['logDbImportSaved', -10],
            ],
            'model.episodeOfCareModel.saved' => [
                ['logFileImportSaved', -10],
                ['logDbImportSaved', -10],
            ],
            'model.respondentModel.saved' => [
                ['logFileImportSaved', -10],
                ['logDbImportSaved', -10],
            ],

            'model.appointmentModel.save.error' => [
                ['logFileImportErrors', -10],
                ['logDbImportErrors', -10],
            ],
            'model.conditionModel.save.error' => [
                ['logFileImportErrors', -10],
                ['logDbImportErrors', -10],
            ],
            'model.encounterModel.save.error' => [
                ['logFileImportErrors', -10],
                ['logDbImportErrors', -10],
            ],
            'model.episodeOfCareModel.save.error' => [
                ['logFileImportErrors', -10],
                ['logDbImportErrors', -10],
            ],
            'model.respondentModel.save.error' => [
                ['logFileImportErrors', -10],
                ['logDbImportErrors', -10],
            ],
        ];
    }

    protected function getOrganizationIdFromData(string $resourceName, array $data)
    {
        switch($resourceName) {
            case 'appointment':
            case 'encounter':
                if (isset($data['gap_id_organization'])) {
                    return $data['gap_id_organization'];
                }
                break;
            case 'episodeofcare':
                if (isset($data['gec_id_organization'])) {
                    return $data['gec_id_organization'];
                }
                break;
            case 'respondent':
                if (isset($data['gr2o_id_organization'])) {
                    return $data['gr2o_id_organization'];
                }
                break;
            default:
                return null;
        }

        return null;
    }

    protected function getResourceIdFromData(string $resourceName, array $data)
    {
        switch($resourceName) {
            case 'appointment':
            case 'encounter':
                if (isset($data['gap_id_in_source'])) {
                    return $data['gap_id_in_source'];
                }
                break;
            case 'condition':
                if (isset($data['gmco_id_source'])) {
                    return $data['gmco_id_source'];
                }
                break;
            case 'episodeofcare':
                if (isset($data['gec_id_in_source'])) {
                    return $data['gec_id_in_source'];
                }
                break;
            case 'respondent':
                if (isset($data['gr2o_epd_id'])) {
                    return $data['gr2o_epd_id'];
                }
                break;
            default:
                return null;
        }
        return null;
    }

    protected function getUserIdFromData(string $resourceName, array $data)
    {
        switch($resourceName) {
            case 'appointment':
            case 'encounter':
                if (isset($data['gap_id_user'])) {
                    return $data['gap_id_user'];
                }
                break;
            case 'condition':
                if (isset($data['gmco_id_user'])) {
                    return $data['gmco_id_user'];
                }
                break;
            case 'episodeofcare':
                if (isset($data['gec_id_user'])) {
                    return $data['gec_id_user'];
                }
                break;
            case 'respondent':
                if (isset($data['gr2o_id_user'])) {
                    return $data['gr2o_id_user'];
                }
                break;
            default:
                return null;
        }
        return null;
    }

    public function logDbImportErrors(SaveFailedModel $event)
    {
        $model = $event->getModel();
        $resourceName = strtolower(str_replace('Model', '', $model->getName()));

        $saveData = $event->getSaveData();

        $userId = $this->getUserIdFromData($resourceName, $saveData);
        $organizationId = $this->getOrganizationIdFromData($resourceName, $saveData);

        $exception = $event->getException();
        $errors = $exception->getMessage();
        if ($exception instanceof ModelValidationException) {
            $validationErrors = $exception->getErrors();
            foreach($validationErrors as $field=>$fieldErrors) {
                foreach($fieldErrors as $error) {
                    $errors .= "\n$field: $error";
                }
            }
        }

        $now = new \DateTimeImmutable();

        $data = [
            'geir_source' => $this->epdRepository->getEpdName(),
            'geir_type' => $resourceName,
            'geir_id_user' => $userId,
            'geir_id_organization' => $organizationId,
            'geir_status' => 'failed',
            'geir_errors' => $errors,
            'geir_changed' => $now->format('Y-m-d H:i:s'),
            'geir_changed_by' => $this->currentUserRepository->getUserId(),
            'geir_created' => $now->format('Y-m-d H:i:s'),
            'geir_created_by' => $this->currentUserRepository->getUserId(),
        ];

        $this->importDbLogRepository->logImportResource($data);
    }

    public function logDbImportSaved(SavedModel $event)
    {
        $model = $event->getModel();
        $resourceName = strtolower(str_replace('Model', '', $model->getName()));

        $newValues = $event->getNewData();

        $userId = $this->getUserIdFromData($resourceName, $newValues);
        $organizationId = $this->getOrganizationIdFromData($resourceName, $newValues);

        $now = new \DateTimeImmutable();

        $data = [
            'geir_source' => $this->epdRepository->getEpdName(),
            'geir_type' => $resourceName,
            'geir_id_user' => $userId,
            'geir_id_organization' => $organizationId,
            'geir_status' => 'saved',
            'geir_duration' => $event->getDurationInSeconds(),
            'geir_changed' => $now->format('Y-m-d H:i:s'),
            'geir_changed_by' => $this->currentUserRepository->getUserId(),
            'geir_created' => $now->format('Y-m-d H:i:s'),
            'geir_created_by' => $this->currentUserRepository->getUserId(),
        ];

        $this->importDbLogRepository->logImportResource($data);
    }

    public function logFileImportErrors(SaveFailedModel $event)
    {
        $model = $event->getModel();
        $resourceName = strtolower(str_replace('Model', '', $model->getName()));

        $saveData = $event->getSaveData();
        if(isset($saveData['id'])) {
            $resourceId = $saveData['id'];
        }

        $exception = $event->getException();

        $importLogger = $this->importLogRepository->getImportLogger();
        $message = sprintf('%s: %s', $resourceId, $exception->getMessage());

        $errors = [];
        if ($exception instanceof ModelValidationException) {
            $errors = $exception->getErrors();
        }

        $importLogger->error($message, $errors);
    }

    public function logFileImportSaved(SavedModel $event)
    {
        $model = $event->getModel();
        $resourceName = strtolower(str_replace('Model', '', $model->getName()));

        $newValues = $event->getNewData();
        $resourceId = $this->getResourceIdFromData($resourceName, $newValues);



        $importLogger = $this->importLogRepository->getImportLogger();
        $message = sprintf('Finished import of %s with ID %s', $resourceName, $resourceId);

        $updateData = [];
        if (method_exists($model, 'getUpdateDiffFields')) {
            $updateData = $model->getUpdateDiffFields();
        }
        $importLogger->notice($message, $updateData);
    }

    public function logFileImportStart(ModelImport $event)
    {
        $model = $event->getModel();
        $resourceName = strtolower(str_replace('Model', '', $model->getName()));
        $importData = $event->getImportData();

        $importId = null;
        if (isset($importData['id'])) {
            $importId = $importData['id'];
        }

        $importLogger = $this->importLogRepository->getImportLogger();
        $message = sprintf('Starting import of %s with ID %s', $resourceName, $importId);
        $importLogger->notice($message, $importData);
    }
}
