<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Action;


use Gems\Event\EventDispatcher;
use Gems\Rest\Action\ModelRestControllerAbstract;
use Gems\Rest\Model\ModelException;
use Gems\Rest\Model\ModelValidationException;
use Gems\Rest\Repository\AccesslogRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Exception\InvalidArgumentException;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Emma\Fhir\Event\BeforeSaveModel;
use Pulse\Api\Emma\Fhir\Event\DeleteResourceEvent;
use Pulse\Api\Emma\Fhir\Event\DeleteResourceFailedEvent;
use Pulse\Api\Emma\Fhir\Event\ModelImport;
use Pulse\Api\Emma\Fhir\Event\SavedModel;
use Pulse\Api\Emma\Fhir\Event\SaveFailedModel;
use Pulse\Api\Emma\Fhir\Model\Transformer\CreatedChangedByTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\DateTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ValidateFieldsTransformer;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Zalt\Loader\ProjectOverloader;

class ResourceActionAbstract extends ModelRestControllerAbstract
{
    /**
     * @var EventDispatcher
     */
    protected $event;

    protected $requestStart;
    /**
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;

    public function __construct(CurrentUserRepository $currentUserRepository, EventDispatcher $event, AccesslogRepository $accesslogRepository, ProjectOverloader $loader, UrlHelper $urlHelper, $LegacyDb)
    {
        $this->event = $event;
        parent::__construct($accesslogRepository, $loader, $urlHelper, $LegacyDb);
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

    protected function createModel()
    {
        if (!($this->model instanceof \MUtil_Model_ModelAbstract)) {
            throw new ModelException('No model set in action');
        }
        return $this->model;
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
        $this->requestStart = microtime(true);
        $id = $request->getAttribute('id');
        if ($id === null) {
            return new EmptyResponse(404);
        }

        $this->currentUserRepository->setRequest($request);

        $event = new DeleteResourceEvent($this->model, $id);
        $event->setStart($this->requestStart);
        $this->event->dispatch($event, 'resource.' . $this->model->getName() . '.delete');

        try {
            $changedRows = $this->deleteResourceFromSourceId($id);
        } catch (\Exception $e) {
            $failedEvent = new DeleteResourceFailedEvent($this->model, $e, $id);
            $this->event->dispatch($failedEvent, 'resource.' . $this->model->getName() . '.delete.error');
            return new JsonResponse(['error' => 'unknown_error', 'message' => $e->getMessage()], 400);
        }

        if ($changedRows == 0) {
            return new EmptyResponse(404);
        }

        $this->event->dispatch($event, 'resource.' . $this->model->getName() . '.deleted');

        return new EmptyResponse(204);
    }

    public function deleteResourceFromSourceId($sourceId)
    {
        return 0;
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

        $this->model->addTransformer(new CreatedChangedByTransformer($this->currentUserRepository));
        $this->model->addTransformer(new ValidateFieldsTransformer($this->loader, (int)$request->getAttribute('user_id')));
        $this->model->addTransformer(new DateTransformer());

        $response = $this->saveRow($request, $translatedRow, false);
        if (in_array($response->getStatusCode(), [200,201])) {
            $this->event->dispatch($event, 'emma.import.finish');
        }
        return $response;
    }

    /**
     * Saves the row to the model after validating the row first
     *
     * Hooks beforeSaveRow before validation and afterSaveRow after for extra actions to the row.
     *
     * @param ServerRequestInterface $request
     * @param $row
     * @return EmptyResponse|JsonResponse
     */
    public function saveRow(ServerRequestInterface $request, $row, $update=false)
    {
        if (empty($row)) {
            return new EmptyResponse(400);
        }

        $row = $this->filterColumns($row, true);

        $row = $this->beforeSaveRow($row);

        try {
            $newRow = $this->model->save($row);
        } catch(\Exception $e) {
            // Row could not be saved.

            $event = new SaveFailedModel($this->model);
            $event->setSaveData($row);
            $event->setException($e);

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

        $statusCode = 201;
        if (isset($newRow['exists']) && $newRow['exists'] === true) {
            $statusCode = 200;
        }

        $newRow = $this->afterSaveRow($newRow);

        $idField = $this->getIdField();

        $routeParams = [];
        if (is_array($idField)) {
            foreach ($idField as $key => $singleField) {
                if (isset($newRow[$singleField])) {
                    $routeParams[$key] = $newRow[$singleField];
                } else {
                    return new EmptyResponse(201);
                }
            }
        } elseif (isset($newRow[$idField])) {
            $routeParams[$idField] = $newRow[$idField];
        }

        if (!empty($routeParams)) {

            $result = $request->getAttribute(RouteResult::class);
            $routeName = $result->getMatchedRouteName();
            $baseRoute = str_replace(['.structure', '.get', '.fixed'], '', $routeName);

            $routeParts = explode('.', $baseRoute);
            //array_pop($routeParts);
            $getRouteName = join('.', $routeParts) . '.get';

            try {
                $location = $this->helper->generate($getRouteName, $routeParams);
            } catch(InvalidArgumentException $e) {
                // Give it another go for custom routes
                $getRouteName = join('.', $routeParts);
                try {
                    $location = $this->helper->generate($getRouteName, $routeParams);
                } catch(InvalidArgumentException $e) {
                    $location = null;
                }
            }
            if ($location !== null) {
                /*return new EmptyResponse(
                    $statusCode,
                    [
                        'Location' => $location,
                    ]
                );*/
            }
        }

        return new EmptyResponse($statusCode);
    }
}
