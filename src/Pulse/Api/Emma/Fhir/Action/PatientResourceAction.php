<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Action;


use Gems\Event\EventDispatcher;
use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Exception\MissingDataException;
use Gems\Rest\Model\ModelException;
use Gems\Rest\Model\ModelProcessor;
use Gems\Rest\Model\ModelTranslateException;
use Gems\Rest\Model\ModelValidationException;
use Gems\Rest\Repository\AccesslogRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Emma\Fhir\Event\BeforeSaveModel;
use Pulse\Api\Emma\Fhir\Event\DeleteResourceEvent;
use Pulse\Api\Emma\Fhir\Event\DeleteResourceFailedEvent;
use Pulse\Api\Emma\Fhir\Event\ModelImport;
use Pulse\Api\Emma\Fhir\Event\RespondentMergeEvent;
use Pulse\Api\Emma\Fhir\Event\SavedModel;
use Pulse\Api\Emma\Fhir\Event\SaveFailedModel;
use Pulse\Api\Emma\Fhir\ExistingEpdPatientRepository;
use Pulse\Api\Emma\Fhir\Model\Transformer\CreatedChangedByTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientIdentifierTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ValidateFieldsTransformer;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Emma\Fhir\Repository\EpdRepository;
use Pulse\Api\Emma\Fhir\Repository\EscrowOrganizationRepository;
use Pulse\Api\Emma\Fhir\Repository\RespondentSsnSkipRepository;
use Pulse\Api\Repository\RespondentRepository;
use Zalt\Loader\ProjectOverloader;

class PatientResourceAction extends ModelRestController
{
    /**
     * @var array List of allowed content types as input for write methods
     */
    protected $allowedContentTypes = [
        'application/json',
        'application/fhir+json',
    ];

    /**
     * @var EventDispatcher
     */
    protected $event;

    /**
     * @var ExistingEpdPatientRepository
     */
    protected $existingEpdPatientRepository;

    protected $update = false;
    /**
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;
    /**
     * @var RespondentRepository
     */
    protected $respondentRepository;
    /**
     * @var EpdRepository
     */
    protected $epdRepository;
    /**
     * @var EscrowOrganizationRepository
     */
    protected $escrowOrganizationRepository;

    /**
     * @var RespondentSsnSkipRepository
     */
    protected $respondentSsnSkipRepository;


    public function __construct(RespondentRepository $respondentRepository,
        EpdRepository $epdRepository,
        CurrentUserRepository $currentUserRepository,
        EscrowOrganizationRepository $escrowOrganizationRepository,
        EventDispatcher $event,
        ExistingEpdPatientRepository $existingEpdPatientRepository,
        AccesslogRepository $accesslogRepository,
        RespondentSsnSkipRepository $respondentSsnSkipRepository,
        ProjectOverloader $loader,
        UrlHelper $urlHelper,

        $LegacyDb)
    {
        $this->existingEpdPatientRepository = $existingEpdPatientRepository;
        parent::__construct($accesslogRepository, $loader, $urlHelper, $LegacyDb);
        $this->event = $event;
        $this->currentUserRepository = $currentUserRepository;
        $this->respondentRepository = $respondentRepository;
        $this->epdRepository = $epdRepository;
        $this->escrowOrganizationRepository = $escrowOrganizationRepository;
        $this->respondentSsnSkipRepository = $respondentSsnSkipRepository;
    }

    protected function addRespondentInfoToEvent(DeleteResourceEvent $event, $sourceId)
    {
        $episode = $this->respondentRepository->getRespondentInfoFromEpdId($sourceId, $this->epdRepository->getEpdName());
        if ($episode) {
            $event->setRespondentId($episode['gr2o_id_user']);
        }
    }

    protected function afterSaveRow($newRow)
    {
        $event = new SavedModel($this->model);
        $event->setNewData($newRow);
        $oldData = $this->model->getOldValues();
        $event->setOldData($oldData);
        $event->setStart($this->requestStart);
        $this->event->dispatch($event, 'model.' . $this->model->getName() . '.saved');
        return parent::afterSaveRow($newRow);
    }

    protected function beforeSaveRow($beforeData)
    {
        $event = new BeforeSaveModel($this->model);
        $event->setBeforeData($beforeData);
        $this->event->dispatch($event, 'model.' . $this->model->getName() . '.before-save');
        return parent::beforeSaveRow($beforeData);
    }

    public function put(ServerRequestInterface $request)
    {
        $this->requestStart = microtime(true);
        if ($this->checkContentType($request) === false) {
            return new EmptyResponse(415);
        }

        $parsedBody = json_decode($request->getBody()->getContents(), true);

        if (empty($parsedBody)) {
            return new EmptyResponse(400);
        }

        $this->currentUserRepository->setRequest($request);

        $event = new ModelImport($this->model);
        $event->setImportData($parsedBody);
        $this->event->dispatch($event, 'emma.import.start');

        $translatedRow = $this->translateRow($parsedBody, true);

        $this->logRequest($request, $translatedRow, false);

        try {
            $patientRows = $this->getPatients($translatedRow);
        } catch(\Exception $e) {
            // Row could not be saved.
            // return JsonResponse

            $event = new SaveFailedModel($this->model);
            $event->setException($e);
            $event->setSaveData($translatedRow);

            $this->event->dispatch($event, 'model.' . $this->model->getName() . '.save.error');

            if ($e instanceof ModelException) {
                return new JsonResponse(['error' => 'model_error', 'message' => $e->getMessage()], 400);
            }

            // Unknown exception!
            return new JsonResponse(['error' => 'unknown_error', 'message' => $e->getMessage()], 400);
        }

        if (count($patientRows) === 0) {
            return new JsonResponse(['error' => 'missing_data', 'message' => 'Patient not found'], 400);
        }

        $this->model->addTransformer(new CreatedChangedByTransformer($this->currentUserRepository));
        $this->model->addTransformer(new ValidateFieldsTransformer($this->loader, (int)$request->getAttribute('user_id')));

        $response = $this->saveRows($request, $patientRows, $this->update);
        if (in_array($response->getStatusCode(), [200,201])) {
            $this->event->dispatch($event, 'emma.import.finish');
        }
        return $response;
    }

    public function getPatients($row)
    {
        $patientIdentifierTransformer = new PatientIdentifierTransformer();
        $row = $patientIdentifierTransformer->transformRowBeforeSave($this->model, $row);

        $savePatients = [];

        if (!isset($row['gr2o_patient_nr'])) {
            throw new MissingDataException('No patient number found');
        }
        if (!isset($row['grs_ssn'])) {
            $row['grs_ssn'] = null;
        }
        if ($row['grs_ssn'] && $this->respondentSsnSkipRepository->skipSsn($row['gr2o_patient_nr'])) {
            $this->checkMergeWhenSkipped($row);
            $row['grs_ssn'] = null;
        }

        $existingPatients = $this->existingEpdPatientRepository->getExistingPatients($row['grs_ssn'], $row['gr2o_patient_nr']);

        $removeNewSsn = false;
        if ($existingPatients && isset($existingPatients['removeNewSsn'])) {
            $removeNewSsn = true;
            unset($existingPatients['removeNewSsn']);
        }

        if ($existingPatients) {
            $knownInCurrentEpd = false;
            foreach ($existingPatients as $existingPatient) {
                if (array_key_exists('gor_epd', $existingPatient) && $existingPatient['gor_epd'] != $this->epdRepository->getEpdName()) {
                    continue;
                }
                $knownInCurrentEpd = true;
                $newPatient = $existingPatient + $row;
                if ($removeNewSsn && isset($newPatient['grs_ssn'])) {
                    if (!array_key_exists('importInfo', $newPatient)) {
                        $newPatient['importInfo'] = null;
                    }

                    $newPatient['importInfo'] .= sprintf("SSN %s removed as it is used in another patient.\n", $newPatient['grs_ssn']);
                    $newPatient['grs_ssn'] = null;
                }
                $savePatients[] = $newPatient;
            }

            $this->update = true;

            if (!$knownInCurrentEpd) {
                $firstOtherEpdPatient = reset($existingPatients);
                $row['gr2o_id_user'] = $firstOtherEpdPatient['gr2o_id_user'];
                $row['gr2o_id_organization'] = $this->escrowOrganizationRepository->getId();
                if ($removeNewSsn && isset($row['grs_ssn'])) {
                    $row['grs_ssn'] = null;
                    if (!array_key_exists('importInfo', $row)) {
                        $row['importInfo'] = null;
                    }
                    $row['importInfo'] .= sprintf("SSN %s removed as it is used in another patient.\n", $row['grs_ssn']);
                }
                $savePatients[] = $row;
                $this->update = false;
            }
        } else {
            $row['gr2o_id_organization'] = $this->escrowOrganizationRepository->getId();
            if ($removeNewSsn && isset($row['grs_ssn'])) {
                $row['grs_ssn'] = null;
                if (!array_key_exists('importInfo', $row)) {
                    $row['importInfo'] = null;
                }
                $row['importInfo'] .= sprintf("SSN %s removed as it is used in another patient.\n", $row['grs_ssn']);
            }
            $savePatients[] = $row;
        }

        return $savePatients;
    }

    public function saveRows(ServerRequestInterface $request, $rows, $update)
    {
        foreach($rows as $row) {
            $row = $this->filterColumns($row, true);
            $row = $this->beforeSaveRow($row);
            if (isset($row['gr2o_patient_nr'])) {
                $row['patientNr'] = $row['gr2o_patient_nr'];
            }

            $row['exists'] = false;
            if ($update) {
                $row['exists'] = true;
            }

            try {
                $newRow = $this->model->save($row);
                $this->afterSaveRow($newRow);
            } catch(\Exception $e) {
                // Row could not be saved.
                // return JsonResponse

                $event = new SaveFailedModel($this->model);
                $event->setException($e);
                $event->setSaveData($row);

                $this->event->dispatch($event, 'model.' . $this->model->getName() . '.save.error');

                if ($e instanceof ModelValidationException) {
                    //$this->logger->error($e->getMessage(), $e->getErrors());
                    return new JsonResponse(['error' => 'validation_error', 'message' => $e->getMessage(), 'errors' => $e->getErrors()], 400);
                }

                if ($e instanceof ModelException) {
                    //$this->logger->error($e->getMessage());
                    return new JsonResponse(['error' => 'model_error', 'message' => $e->getMessage()], 400);
                }

                // Unknown exception!
                //$this->logger->error($e->getMessage());
                return new JsonResponse(['error' => 'unknown_error', 'message' => $e->getMessage()], 400);
            }
            $this->requestStart = microtime(true);

        }

        if ($update) {
            return new EmptyResponse(200);
        }
        return new EmptyResponse(201);
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
        $this->requestStart = microtime(true);

        $this->currentUserRepository->setRequest($request);

        $event = new DeleteResourceEvent($this->model, $id);
        $event->setStart($this->requestStart);
        $this->event->dispatch($event, 'resource.' . $this->model->getName() . '.delete');

        try {
            $changedRows = $this->respondentRepository->softDeletePatientFromSourceId($id, $this->epdRepository->getEpdName());
        } catch (\Exception $e) {
            $failedEvent = new DeleteResourceFailedEvent($this->model, $e, $id);
            $this->event->dispatch($failedEvent, 'resource.' . $this->model->getName() . '.delete.error');
            return new JsonResponse(['error' => 'unknown_error', 'message' => $e->getMessage()], 400);
        }

        if ($changedRows == 0) {
            return new EmptyResponse(404);
        }

        $this->addRespondentInfoToEvent($event, $id);

        $this->event->dispatch($event, 'resource.' . $this->model->getName() . '.deleted');

        return new EmptyResponse(204);
    }

    /**
     * @param array $row
     * @return void
     */
    protected function checkMergeWhenSkipped($row)
    {
        $existingPatients = $this->respondentRepository->getPatientsFromSsn($row['grs_ssn'], $this->epdRepository->getEpdName());
        if (!count($existingPatients)) {
            return;
        }

        $existingPatient = reset($existingPatients);
        if (!isset($existingPatient['gr2o_patient_nr'])) {
            return;
        }

        $respondentMergeEvent = new RespondentMergeEvent();
        $respondentMergeEvent->setOldPatientNr($existingPatient['gr2o_patient_nr']);
        $respondentMergeEvent->setNewPatientNr($row['gr2o_patient_nr']);
        $respondentMergeEvent->setSsn($row['grs_ssn']);
        $respondentMergeEvent->setEpd($this->epdRepository->getEpdName());
        $respondentMergeEvent->setStatus('new-ssn-removed');
        $this->event->dispatch($respondentMergeEvent, 'respondent.merge');
    }
}
