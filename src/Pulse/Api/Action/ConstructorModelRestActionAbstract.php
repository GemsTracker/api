<?php

declare(strict_types=1);


namespace Pulse\Api\Action;


use Gems\Event\EventDispatcher;
use Gems\Rest\Action\ModelRestControllerAbstract;
use Gems\Rest\Model\ModelException;
use Gems\Rest\Model\ModelValidationException;
use Gems\Rest\Repository\AccesslogRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Exception\InvalidArgumentException;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Emma\Fhir\Event\BeforeSaveModel;
use Pulse\Api\Emma\Fhir\Event\SavedModel;
use Pulse\Api\Emma\Fhir\Event\SaveFailedModel;
use Pulse\Api\Emma\Fhir\Model\Transformer\CreatedChangedByTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\DateTransformer;
use Pulse\Api\Emma\Fhir\Model\Transformer\ValidateFieldsTransformer;
use Pulse\Api\Emma\Fhir\Repository\CurrentUserRepository;
use Pulse\Api\Repository\RequestRepository;
use Zalt\Loader\ProjectOverloader;

class ConstructorModelRestActionAbstract extends ModelRestControllerAbstract
{
    /**
     * @var EventDispatcher
     */
    protected $event;
    /**
     * @var CurrentUserRepository
     */
    protected $currentUserRepository;
    /**
     * @var RequestRepository
     */
    protected $requestRepository;

    public function __construct(\MUtil_Model_ModelAbstract $model, EventDispatcher $eventDispatcher, CurrentUserRepository $currentUserRepository, RequestRepository $requestRepository, AccesslogRepository $accesslogRepository, ProjectOverloader $loader, UrlHelper $urlHelper, $LegacyDb)
    {
        $this->model = $model;
        $this->event = $eventDispatcher;
        parent::__construct($accesslogRepository, $loader, $urlHelper, $LegacyDb);
        $this->currentUserRepository = $currentUserRepository;
        $this->requestRepository = $requestRepository;
    }

    protected function afterSaveRow($newRow)
    {
        $event = new SavedModel($this->model);
        $event->setNewData($newRow);
        if (method_exists($this->model, 'getOldValues')) {
            $oldData = $this->model->getOldValues();
            $event->setOldData($oldData);
        }

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

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $this->requestStart = microtime(true);
        $this->currentUserRepository->setRequest($request);
        $this->requestRepository->setRequest($request);
        return parent::process($request, $delegate);
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

        $this->model->addTransformer(new CreatedChangedByTransformer($this->currentUserRepository));
        $this->model->addTransformer(new ValidateFieldsTransformer($this->loader, (int)$this->currentUserRepository->getUserId()));
        $this->model->addTransformer(new DateTransformer());

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
