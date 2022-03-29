<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Action;


use Gems\Event\EventDispatcher;
use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Model\ModelException;
use Gems\Rest\Model\ModelProcessor;
use Gems\Rest\Model\ModelValidationException;
use Gems\Rest\Repository\AccesslogRepository;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Emma\Fhir\Event\BeforeSaveModel;
use Pulse\Api\Emma\Fhir\Event\ModelImport;
use Pulse\Api\Emma\Fhir\Event\SavedModel;
use Pulse\Api\Emma\Fhir\Event\SaveFailedModel;
use Pulse\Api\Emma\Fhir\ExistingEpdPatientRepository;
use Pulse\Api\Emma\Fhir\Model\Transformer\CreatedChangedByTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\PatientIdentifierTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ValidateFieldsTransformer;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Zalt\Loader\ProjectOverloader;

class PatientResourceAction extends ModelRestController
{
    protected $escrowOrganizationId = 81;

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


    public function __construct(CurrentUserRepository $currentUserRepository, EventDispatcher $event, ExistingEpdPatientRepository $existingEpdPatientRepository, AccesslogRepository $accesslogRepository, ProjectOverloader $loader, UrlHelper $urlHelper, $LegacyDb)
    {
        $this->existingEpdPatientRepository = $existingEpdPatientRepository;
        parent::__construct($accesslogRepository, $loader, $urlHelper, $LegacyDb);
        $this->event = $event;
        $this->currentUserRepository = $currentUserRepository;
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

        $patientRows = $this->getPatients($translatedRow);

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

        if (isset($row['grs_ssn'], $row['gr2o_patient_nr'])) {
            $existingPatients = $this->existingEpdPatientRepository->getExistingPatients($row['grs_ssn'], $row['gr2o_patient_nr']);

            if ($existingPatients) {
                foreach ($existingPatients as $existingPatient) {
                    $savePatients[] = $existingPatient + $row;
                }
                $this->update = true;
            } else {
                $row['gr2o_id_organization'] = $this->escrowOrganizationId;
                $savePatients[] = $row;
            }
        }

        return $savePatients;
    }

    public function saveRows(ServerRequestInterface $request, $rows, $update)
    {
        foreach($rows as $row) {
            $row = $this->filterColumns($row, true);
            $row = $this->beforeSaveRow($row);

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
}