<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir;


use Gems\Rest\Model\ModelValidationException;
use Pulse\Api\Emma\Fhir\Event\DeleteResourceEvent;
use Pulse\Api\Emma\Fhir\Event\DeleteResourceFailedEvent;
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
                ['logDbEpdLogSaved', -10],
            ],
            'model.conditionModel.saved' => [
                ['logFileImportSaved', -10],
                ['logDbImportSaved', -10],
            ],
            'model.encounterModel.saved' => [
                ['logFileImportSaved', -10],
                ['logDbImportSaved', -10],
                ['logDbEpdLogSaved', -10],
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

            'resource.appointmentModel.delete' => [
                ['logFileImportDeleteStart', -10],
            ],

            'resource.appointmentModel.deleted' => [
                ['logFileImportDeleted', -10],
                ['logDbImportDeleted', -10],
            ],

            'resource.appointmentModel.delete.error' => [
                ['logFileImportDeleteError', -10],
                ['logDbImportDeleteError', -10],
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

    public function logDbEpdLogSaved(SavedModel $event)
    {
        $model = $event->getModel();
        $resourceName = strtolower(str_replace('Model', '', $model->getName()));

        $newValues = $event->getNewData();

        $userId = $this->getUserIdFromData($resourceName, $newValues);
        $organizationId = $this->getOrganizationIdFromData($resourceName, $newValues);

        $now = new \DateTimeImmutable();

        $isChange = 0;
        if (isset($newValues['exists']) && $newValues['exists'] === true) {
            $isChange = 1;
        }

        $admissionTime = $newValues['gap_admission_time'];
        if ($admissionTime instanceof \MUtil_Date) {
            $admissionTime = $admissionTime->toString('yyyy-MM-dd HH:mm:ss');
        }

        $description = $isChange ? 'Changed' : 'Created';
        $description .= ' appointment for ';
        $description .= $userId;
        $description .= ' with ';
        $description .= $organizationId;
        $description .= ' on ';
        $description .= $admissionTime;

        $data = [
            'pls_appointment_id' => $newValues['gap_id_appointment'],
            'pls_id_respondent' => $newValues['gap_id_user'],
            'pls_patient_nr' => '',
            'pls_id_organization' => $newValues['gap_id_organization'],
            'pls_is_change' => $isChange,
            'pls_description' => $description,
            'pls_created' => $now->format('Y-m-d H:i:s'),
        ];

        $this->importDbLogRepository->logEpdChange($data);
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
        $resourceId = $this->getResourceIdFromData($resourceName, $newValues);

        $now = new \DateTimeImmutable();

        $isNew = 1;
        if (isset($newValues['exists']) && $newValues['exists'] === true) {
            $isNew = 0;
        }

        $data = [
            'geir_source' => $this->epdRepository->getEpdName(),
            'geir_resource_id' => $resourceId,
            'geir_type' => $resourceName,
            'geir_id_user' => $userId,
            'geir_id_organization' => $organizationId,
            'geir_status' => 'saved',
            'geir_duration' => $event->getDurationInSeconds(),
            'geir_new' => $isNew,
            'geir_changed' => $now->format('Y-m-d H:i:s'),
            'geir_changed_by' => $this->currentUserRepository->getUserId(),
            'geir_created' => $now->format('Y-m-d H:i:s'),
            'geir_created_by' => $this->currentUserRepository->getUserId(),
        ];

        $this->importDbLogRepository->logImportResource($data);
    }

    public function logDbImportDeleted(DeleteResourceEvent $event)
    {
        $model = $event->getModel();
        $resourceName = strtolower(str_replace('Model', '', $model->getName()));

        $resourceId = $event->getResourceId();

        $now = new \DateTimeImmutable();

        $data = [
            'geir_source' => $this->epdRepository->getEpdName(),
            'geir_resource_id' => $resourceId,
            'geir_type' => $resourceName,
            'geir_status' => 'deleted',
            'geir_duration' => $event->getDurationInSeconds(),
            'geir_new' => 0,
            'geir_changed' => $now->format('Y-m-d H:i:s'),
            'geir_changed_by' => $this->currentUserRepository->getUserId(),
            'geir_created' => $now->format('Y-m-d H:i:s'),
            'geir_created_by' => $this->currentUserRepository->getUserId(),
        ];

        $this->importDbLogRepository->logImportResource($data);
    }

    public function logDbImportDeleteError(DeleteResourceFailedEvent $event)
    {
        $model = $event->getModel();
        $resourceName = strtolower(str_replace('Model', '', $model->getName()));

        $resourceId = $event->getResourceId();

        $exception = $event->getException();
        $errors = $exception->getMessage();

        $now = new \DateTimeImmutable();

        $data = [
            'geir_source' => $this->epdRepository->getEpdName(),
            'geir_resource_id' => $resourceId,
            'geir_type' => $resourceName,
            'geir_status' => 'deleteFailed',
            'geir_errors' => $errors,
            'geir_new' => 0,
            'geir_changed' => $now->format('Y-m-d H:i:s'),
            'geir_changed_by' => $this->currentUserRepository->getUserId(),
            'geir_created' => $now->format('Y-m-d H:i:s'),
            'geir_created_by' => $this->currentUserRepository->getUserId(),
        ];

        $this->importDbLogRepository->logImportResource($data);
    }

    public function logFileImportDeleteStart(DeleteResourceEvent $event)
    {
        $resourceId = $event->getResourceId();
        $model = $event->getModel();
        $resourceName = strtolower(str_replace('Model', '', $model->getName()));

        $importLogger = $this->importLogRepository->getImportLogger();
        $message = sprintf('Starting deletion of %s with ID %s', $resourceName, $resourceId);
        $importLogger->notice($message);
    }

    public function logFileImportDeleted(DeleteResourceEvent $event)
    {
        $resourceId = $event->getResourceId();
        $model = $event->getModel();
        $resourceName = strtolower(str_replace('Model', '', $model->getName()));

        $importLogger = $this->importLogRepository->getImportLogger();
        $message = sprintf('Finished deletion of %s with ID %s', $resourceName, $resourceId);
        $importLogger->notice($message);
    }

    public function logFileImportDeleteError(DeleteResourceFailedEvent $event)
    {
        $resourceId = $event->getResourceId();
        $model = $event->getModel();
        $resourceName = strtolower(str_replace('Model', '', $model->getName()));

        $exception = $event->getException();

        $importLogger = $this->importLogRepository->getImportLogger();
        $message = sprintf('Deleting %s [%s] failed: %s', $resourceName, $resourceId, $exception->getMessage());

        $importLogger->error($message);
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
        $message = sprintf('Saving %s [%s] failed: %s', $resourceName, $resourceId, $exception->getMessage());

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

        $isNew = 'new';
        if (isset($newValues['exists']) && $newValues['exists'] === true) {
            $isNew = 'existing';
        }

        $importLogger = $this->importLogRepository->getImportLogger();
        $message = sprintf('Finished import of %s %s with ID %s', $isNew, $resourceName, $resourceId);

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
