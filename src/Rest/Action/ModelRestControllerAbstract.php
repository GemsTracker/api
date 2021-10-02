<?php


namespace Gems\Rest\Action;

use Gems\Rest\Model\ModelException;
use Gems\Rest\Model\ModelProcessor;
use Gems\Rest\Model\ModelValidationException;
use Gems\Rest\Model\RouteOptionsModelFilter;
use Gems\Rest\Repository\AccesslogRepository;
use MUtil\Model\Type\JsonData;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Zalt\Loader\ProjectOverloader;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Exception;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;

abstract class ModelRestControllerAbstract extends RestControllerAbstract
{

    /**
     * @var AccesslogRepository
     */
    protected $accesslogRepository;

    /**
     * @var array List of allowed content types as input for write methods
     */
    protected $allowedContentTypes = ['application/json'];

    /**
     * @var array list of translated colnames for the api
     */
    protected $apiNames;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db1;

    /**
     * @var List of errors from validating a row
     */
    protected $errors;

    /**
     * @var UrlHelper
     */
    protected $helper;

    /**
     * @var Fieldname of model that identifies a row with a unique ID
     */
    protected $idField;

    /**
     * @var int number of items per page for pagination
     */
    protected $itemsPerPage = 25;

    /**
     * @var ProjectOverloader
     */
    protected $loader;

    /**
     * @var \MUtil_Model_ModelAbstract Gemstracker Model
     */
    protected $model;

    /**
     * @var array list of apiNames but key=>value reversed
     */
    protected $reverseApiNames;

    /**
     * @var array list of column structure
     */
    protected $structure;

    /**
     * @var array list of methods supported by this current controller
     */
    protected $supportedMethods = [
        'delete', 'get', 'options', 'patch', 'post', 'structure',
    ];

    /**
     * @var array validators of the model fields stored per field
     */
    protected $validators;

    /**
     *
     * RestControllerAbstract constructor.
     * @param ProjectOverloader $loader
     * @param UrlHelper $urlHelper
     * @param $LegacyDb Init Zend DB so it's loaded at least once, needed to set default Zend_Db_Adapter for Zend_Db_Table
     */
    public function __construct(AccesslogRepository $accesslogRepository, ProjectOverloader $loader, UrlHelper $urlHelper, $LegacyDb)
    {
        $this->accesslogRepository = $accesslogRepository;
        $this->loader = $loader;
        //$this->loader->verbose = true;
        //$this->loader->legacyClasses = true;

        $this->helper = $urlHelper;
        $this->db1 = $LegacyDb;
    }

    protected function addCurrentUserToModel()
    {
        \Gems_Model::setCurrentUserId($this->userId);
    }

    /**
     * Add _changed and _changed_by fields, if they exist in the model
     *
     * @param $row
     * @return mixed
     */
    public function addChangeFields($row)
    {
        $columns = $this->model->getItemNames();
        foreach ($columns as $itemName) {
            if (strpos($itemName, '_changed_by') !== false) {
                $row[$itemName] = $this->userId;
            } elseif (strpos($itemName, '_changed') !== false) {
                $now = new \DateTime();
                $row[$itemName] = $now->format('c');
            }
        }
        return $row;
    }

    /**
     * Add _created and _created_by fields, if they exist in the model
     *
     * @param $row
     * @return mixed
     */
    public function addCreateFields($row)
    {
        $columns = $this->model->getItemNames();
        foreach ($columns as $itemName) {
            if (strpos($itemName, '_created_by') !== false) {
                $row[$itemName] = $this->userId;
            } elseif (strpos($itemName, '_created') !== false) {
                $now = new \DateTime();
                $row[$itemName] = $now->format('c');
            }
        }
        return $row;
    }

    /**
     * Load new model data to the current row. Useful for POST requests to load defaults
     *
     * @param $row
     * @return array
     */
    protected function addNewModelRow($row)
    {
        $row += $this->model->loadNew();
        return $row;
    }

    /**
     * Do actions or translate the row after a save
     *
     * @param array $newRow
     * @return array
     */
    protected function afterSaveRow($newRow)
    {
        return $newRow;
    }

    /**
     * Do actions or translate the row before a save and before validators
     *
     * @param array $row
     * @return array
     */
    protected function beforeSaveRow($row)
    {
        return $row;
    }


    /**
     * Check if current content type is allowed for the current method
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function checkContentType(ServerRequestInterface $request)
    {
        $contentTypeHeader = $request->getHeaderLine('content-type');
        foreach ($this->allowedContentTypes as $contentType) {
            if (strpos($contentTypeHeader, $contentType) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a Gemstracker model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    abstract protected function createModel();

    /**
     * Delete a row from the model
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return EmptyResponse
     */
    public function delete(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $request->getAttribute('id');
        $idField = $this->getIdField();
        if ($id === null || !$idField) {
            return new EmptyResponse(404);
        }

        $filter = [
            $idField => $id,
        ];

        if (isset($this->routeOptions['respondent_id_field'])) {
            try {
                $row = $this->model->loadFirst($filter);
                $this->logRequest($request, $row);
            } catch(\Exception $e) {
                return new EmptyResponse(404);
            }
        }

        try {
            $changedRows = $this->model->delete($filter);

        } catch (Exception $e) {
            return new EmptyResponse(400);
        }

        if ($changedRows == 0) {
            return new EmptyResponse(400);
        }

        return new EmptyResponse(204);
    }

    /**
     * Filter the columns of a row with routeoptions like allowed_fields, disallowed_fields and readonly_fields
     *
     * @param array $row Row with model values
     * @param bool $save Will the row be saved after filter (enables readonly
     * @param bool $useKeys Use keys or values in the filter of the row
     * @return array Filtered array
     */
    protected function filterColumns($row, $save=false, $useKeys=true)
    {
        $row = RouteOptionsModelFilter::filterColumns($row, $this->routeOptions, $save, $useKeys);

        return $row;
    }

    protected function flipMultiArray($array)
    {
        $flipped = [];
        foreach($array as $key=>$value)
        {
            if (is_array($value)) {
                $flipped[$key] = $this->flipMultiArray($value);
            } else {
                $flipped[$value] = $key;
            }
        }
        return $flipped;
    }

    /**
     * Get one or multiple rows from the model
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return EmptyResponse|JsonResponse
     */
    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $this->getId($request);

        if ($id !== null) {
            return $this->getOne($id, $request);
        } else {
            return $this->getList($request, $delegate);
        }
    }

    /**
     * Get the allowed filter fields, null if all is allowed
     *
     * @return null|string[]
     */
    protected function getAllowedFilterFields()
    {
        return $this->model->getItemNames();
    }

    /**
     * Get the api column names translations if they are set
     *
     * @param bool $reverse return the reversed translations
     * @return array|null
     */
    protected function getApiNames($reverse=false)
    {
        if (!$this->apiNames) {
            $this->apiNames = $this->getApiSubModelNames($this->model);
        }

        if ($reverse) {
            if (!$this->reverseApiNames) {
                $this->reverseApiNames = $this->flipMultiArray($this->apiNames);
            }
            return $this->reverseApiNames;
        }

        return $this->apiNames;
    }

    protected function getApiSubModelNames($model)
    {
        $apiNames = $this->model->getCol('apiName');

        $subModels = $model->getCol('model');
        foreach($subModels as $subModelName=>$subModel) {
            $apiNames[$subModelName] = $this->getApiSubModelNames($subModel);
        }
        return $apiNames;
    }

    /**
     * Get the ID from the request. e.g. a route to /items/5 will return 5
     *
     * @param ServerRequestInterface $request
     * @return array|mixed|null
     */
    protected function getId(ServerRequestInterface $request)
    {
        if (isset($this->routeOptions['idField'])) {
            if (is_array($this->routeOptions['idField'])) {
                $id = [];
                foreach($this->routeOptions['idField'] as $idField) {
                    if ($subId = $request->getAttribute($idField)) {
                        $id[] = $request->getAttribute($idField);
                    }
                }
                if ($id === []) {
                    $id = null;
                }
            } else {
                $id = $request->getAttribute($this->routeOptions['idField']);
            }

        } else {
            $id = $request->getAttribute('id');
        }

        return $id;
    }

    /**
     * Get the id field of the model if it is set in the model keys
     *
     * @return string Fieldname
     */
    protected function getIdField()
    {
        if (!$this->idField) {
            $keys = $this->model->getKeys();
            if (isset($keys['id'])) {
                $this->idField = $keys['id'];
            }
        }

        return $this->idField;
    }

    /**
     * Return a filter that has the current models id field or fields as parameters set.
     *
     * @param $id
     * @param $idField
     * @return array
     */
    protected function getIdFilter($id, $idField)
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        if (!is_array($idField)) {
            $idField = [$idField];
        }

        $apiNames = $this->getApiNames(true);

        $filter = [];
        foreach($idField as $key=>$singleField) {
            if (isset($apiNames[$singleField])) {
                $singleField = $apiNames[$singleField];
            }
            $filter[$singleField] = $id[$key];
        }

        return $filter;
    }

    /**
     * Get a list of items from the model, filtered in the request attributes
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return EmptyResponse|JsonResponse
     */
    public function getList(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $filters = $this->getListFilter($request);
        $order = $this->getListOrder($request);
        $paginatedFilters = $this->getListPagination($request, $filters);
        $headers = $this->getPaginationHeaders($request, $filters);
        if ($headers === false) {
            return new EmptyResponse(204);
        }

        $rows = $this->model->load($paginatedFilters, $order);

        $translatedRows = [];
        foreach($rows as $key=>$row) {
            $translatedRows[$key] = $this->filterColumns($this->translateRow($row));
        }

        return new JsonResponse($translatedRows, 200, $headers);
    }

    /**
     * Get all filters set in the request attributes used for listing model items with a GET request
     *
     * most common just the columnName=>value
     * values in [] brackets will be checked on special characters <, > <=, >=, LIKE, NOT LIKE for specific operations
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    public function getListFilter(ServerRequestInterface $request)
    {
        $params = $request->getQueryParams();

        $keywords = [
            'per_page',
            'page',
            'order',
        ];

        $keywords = array_flip($keywords);

        $allowedFilterFields = $this->getAllowedFilterFields();

        $translations = $this->getApiNames(true);

        $filters = [];

        foreach($params as $key=>$value) {
            if (isset($keywords[$key])) {
                continue;
            }

            if (isset($this->routeOptions['multiOranizationField'], $this->routeOptions['multiOranizationField']['field'])
                && $key == $this->routeOptions['multiOranizationField']['field']) {
                $field = $this->routeOptions['multiOranizationField']['field'];
                $separator = $this->routeOptions['multiOranizationField']['separator'];
                $organizationIds = $value;
                if (!is_array($organizationIds)) {
                    $organizationIds = explode(',', $organizationIds);
                }

                $organizationFilter = [];
                foreach($organizationIds as $organizationId) {
                    $organizationFilter[] = $field . ' LIKE '. $this->db1->quote('%'.$separator . $organizationId . $separator . '%');
                }
                if (!empty($organizationFilter)) {
                    $filters[] = '(' . join(' OR ', $organizationFilter) . ')';
                }

                continue;
            }

            $colName = $key;
            if (isset($translations[$key])) {
                $colName = $translations[$key];
            }

            if ($allowedFilterFields === null || in_array($colName, $allowedFilterFields)) {
                if (is_string($value) || is_numeric($value)) {
                    if (strpos($value, '[') === 0 && strpos($value, ']') === strlen($value) - 1) {
                        $values = explode(',', str_replace(['[', ']'], '', $value));
                        $firstValue = reset($values);
                        switch ($firstValue) {
                            case '<':
                            case '>':
                            case '<=':
                            case '>=':
                            case '!=':
                            case 'LIKE':
                            case 'NOT LIKE':
                                $secondValue = end($values);
                                if (is_numeric($secondValue)) {
                                    $secondValue = ($secondValue == (int)$secondValue) ? (int)$secondValue : (float)$secondValue;
                                }
                                if ($firstValue == 'LIKE' || $firstValue == 'NOT LIKE') {
                                    $secondValue = $this->db1->quote($secondValue);
                                }
                                $filters[] = $colName . ' ' . $firstValue . ' ' . $secondValue;
                                break;
                            default:
                                $filters[$colName] = $values;
                                break;
                        }
                    } else {
                        switch (strtoupper($value)) {
                            case 'IS NULL':
                            case 'IS NOT NULL':
                                $filters[] = $colName . ' ' . $value;
                                break;
                            default:
                                $filters[$colName] = $value;
                        }
                    }
                } elseif (is_array($value)) {
                    $filters[$colName] = $value;
                }
            }
        }

        return $filters;
    }

    /**
     * Get the order items should be ordered in for listing model items with a GET request
     *
     * @param ServerRequestInterface $request
     * @return bool|array
     */
    public function getListOrder(ServerRequestInterface $request)
    {
        $params = $request->getQueryParams();
        if (isset($params['order'])) {

            if ($params['order'] == 1) {
                return true;
            }

            $orderParams = explode(',', $params['order']);

            $order = [];
            $translations = $this->getApiNames(true);

            foreach($orderParams as $orderParam) {
                $sort = false;
                $name = $orderParam = trim($orderParam);

                if (strpos($orderParam, '-') === 0) {
                    $name = substr($orderParam, 1);
                    $sort = SORT_DESC;
                }
                if (strpos(strtolower($orderParam), ' desc')) {
                    $name = substr($orderParam, 0,-5);
                    $sort = SORT_DESC;
                }
                if (strpos(strtolower($orderParam), ' asc')) {
                    $name = substr($orderParam, 0,-4);
                    $sort = SORT_ASC;
                }

                $name = trim($name);

                if (isset($translations[$name])) {
                    $name = $translations[$name];
                }

                if ($sort) {
                    $order[$name] = $sort;
                } else {
                    $order[] = $name;
                }
            }

            return $order;

        }
        return [];
    }

    /**
     * Get pagination filters for listing model items with a GET request
     *
     * uses per_page and page to set the sql limit
     *
     * @param ServerRequestInterface $request
     * @param $filters
     * @return mixed
     */
    public function getListPagination(ServerRequestInterface $request, $filters)
    {
        $params = $request->getQueryParams();

        if (isset($params['per_page'])) {
            $this->itemsPerPage = $params['per_page'];
        }

        if ($this->itemsPerPage) {
            $page = 1;
            if (isset($params['page'])) {
                $page = $params['page'];
            }
            $offset = ($page-1) * $this->itemsPerPage;

            $filters['limit'] = [
                $this->itemsPerPage,
                $offset,
            ];
        }

        return $filters;
    }

    /**
     * Get one item from the model from an ID field
     *
     * @param $id
     * @param ServerRequestInterface $request
     * @return EmptyResponse|JsonResponse
     */
    public function getOne($id, ServerRequestInterface $request)
    {
        $idField = $this->getIdField();
        if ($idField) {
            $filter = $this->getIdFilter($id, $idField);

            $row = $this->model->loadFirst($filter);
            $this->logRequest($request, $row);
            if (is_array($row)) {
                $translatedRow = $this->translateRow($row);
                $filteredRow = $this->filterColumns($translatedRow);
                return new JsonResponse($filteredRow);
            }
        }
        return new EmptyResponse(404);
    }

    /**
     * Get response headers used for pagination.
     * Will set
     * - X-total-count: the total number of items
     * - page: the current page
     * - Link: links to the previous, next, first and last page if applicable
     *
     * @param ServerRequestInterface $request
     * @param array $filter
     * @param array $sort
     * @return array|bool
     */
    public function getPaginationHeaders(ServerRequestInterface $request, $filter=[], $sort=[])
    {
        $count = $this->model->getItemCount($filter, $sort);

        $headers = [
            'X-total-count' => $count
        ];

        if ($this->itemsPerPage) {
            $params = $request->getQueryParams();

            $page = 1;
            if (isset($params['page'])) {
                $page = $params['page'];
            }

            $lastPage = ceil($count / $this->itemsPerPage);

            if ($page > $lastPage) {
                return false;
            }

            $baseUrl = $request->getUri()
                ->withQuery('')
                ->withFragment('')
                ->__toString();

            $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');
            $routeName   = $routeResult->getMatchedRouteName();

            $links = [];

            if ($page != $lastPage) {
                $nextPageParams = $params;
                $nextPageParams['page'] = $page+1;
                $links['next'] = '<'.$baseUrl.$this->helper->generate($routeName, [], $nextPageParams).'>; rel=next';

                $lastPageParams = $params;
                $lastPageParams['page'] = $lastPage;
                $links['last'] = '<'.$baseUrl.$this->helper->generate($routeName, [], $lastPageParams).'>; rel=last';
            }

            if ($page > 1) {
                $firstPageParams = $params;
                $firstPageParams['page'] = 1;
                $links['first'] = '<'.$baseUrl.$this->helper->generate($routeName, [], $firstPageParams).'>; rel=first';

                $prevPageParams = $params;
                $prevPageParams['page'] = $page-1;
                $links['prev'] = '<'.$baseUrl.$this->helper->generate($routeName, [], $prevPageParams).'>; rel=prev';
            }

            $headers['Link'] = join(',', $links);
        }

        return $headers;
    }

    /**
     * Get the structural information of each model field so it will be easier to see what information is
     * received or needed for a POST/PATCH
     *
     * @return array
     * @throws \Zend_Date_Exception
     */
    public function getStructure()
    {
        if (!$this->structure) {
            $columns = $this->model->getItemsOrdered();

            $translations = $this->getApiNames();

            $structureAttributes = [
                'label',
                'description',
                'required',
                'size',
                'maxlength',
                'type',
                'multiOptions',
                'default',
                'elementClass',
                'multiOptionSettings',
                'disable',
                'raw',
            ];

            $translatedColumns = [];

            foreach($columns as $columnName) {
                $columnLabel = $columnName;
                if (isset($translations[$columnName]) && !empty($translations[$columnName])) {
                    $columnLabel = $translations[$columnName];
                }
                $translatedColumns[$columnName] = $columnLabel;
            }
            $columns = $this->filterColumns($translatedColumns, false, false);

            foreach ($columns as $columnName => $columnLabel) {
                foreach ($structureAttributes as $attributeName) {
                    if ($this->model->has($columnName, $attributeName)) {

                        $propertyValue = $this->model->get($columnName, $attributeName);

                        $structure[$columnLabel][$attributeName] = $propertyValue;

                        if ($attributeName === 'type') {
                            switch ($structure[$columnLabel][$attributeName]) {
                                case 0:
                                    $structure[$columnLabel][$attributeName] = 'no_value';
                                    break;
                                case 1:
                                    $structure[$columnLabel][$attributeName] = 'string';
                                    break;
                                case 2:
                                    $structure[$columnLabel][$attributeName] = 'numeric';
                                    break;
                                case 3:
                                    $structure[$columnLabel][$attributeName] = 'date';
                                    break;
                                case 4:
                                    $structure[$columnLabel][$attributeName] = 'datetime';
                                    break;
                                case 5:
                                    $structure[$columnLabel][$attributeName] = 'time';
                                    break;
                                case 6:
                                    $structure[$columnLabel][$attributeName] = 'child_model';
                                    break;
                                default:
                                    $structure[$columnLabel][$attributeName] = 'no_value';
                                    break;
                            }
                            if ($this->model->has($columnName, \MUtil_Model_ModelAbstract::SAVE_TRANSFORMER)) {
                                $transformer = $this->model->get($columnName, \MUtil_Model_ModelAbstract::SAVE_TRANSFORMER);
                                if (is_array($transformer) && $transformer[0] instanceof JsonData) {
                                    $structure[$columnLabel][$attributeName] = 'json';
                                }
                            }
                        }

                        if ($attributeName == 'default') {
                            switch (true) {
                                case $structure[$columnLabel][$attributeName] instanceof \Zend_Db_Expr:
                                    $structure[$columnLabel][$attributeName] = $structure[$columnLabel][$attributeName]->__toString();
                                    break;
                                case ($structure[$columnLabel][$attributeName] instanceof \MUtil_Date
                                    && $structure[$columnLabel][$attributeName] == new \MUtil_Date):
                                    $structure[$columnLabel][$attributeName] = 'NOW()';
                                    break;
                                case ($structure[$columnLabel][$attributeName] instanceof \Zend_Date
                                    && $structure[$columnLabel][$attributeName] == new \Zend_Date):
                                    $structure[$columnLabel][$attributeName] = 'NOW()';
                                    break;
                                case is_object($structure[$columnLabel][$attributeName]):
                                    $structure[$columnLabel][$attributeName] = null;
                            }
                        }
                    }
                }
                if (isset($structure[$columnLabel])) {
                    $structure[$columnLabel]['name'] = $columnLabel;
                }
            }

            $usedColumns = array_keys($structure);

            $columns = $this->filterColumns($usedColumns, false, false);
            $structure = array_intersect_key($structure, array_flip($columns));

            $this->structure = $structure;
        }

        return $this->structure;
    }

    /**
     * Returns an empty response with the allowed methods for this specific endpoint in the header
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return EmptyResponse
     */
    public function options(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $response = new EmptyResponse(200);

        $allow = null;

        if (isset($this->routeOptions['methods'])) {
            $allow = strtoupper(join(', ', $this->routeOptions['methods']));
        } else {
            $allow = strtoupper(join(', ', $this->supportedMethods));
        }

        $response = $response->withHeader('Allow', $allow);
        $response = $response->withHeader('Access-Control-Allow-Methods', $allow);

        return $response;
    }

    protected function logRequest(ServerRequestInterface $request, $data = null, $changed = false)
    {
        $respondentId = null;
        if ($data && isset($this->routeOptions['respondentIdField']) && isset($data[$this->routeOptions['respondentIdField']])) {
            $respondentId = $data[$this->routeOptions['respondentIdField']];
        }

        if ($changed) {
            return $this->accesslogRepository->logChange($request, $respondentId);
        }

        return $this->accesslogRepository->logAction($request, $respondentId);
    }

    /**
     * Save a new row to the model
     *
     * Will return status:
     * - 415 when the content type of the data supplied in the request is not allowed
     * - 400 (empty response) if the row is empty or if the model could not save the row AFTER validation
     * - 400 (json response) if the row did not pass validation. Errors will be returned in the body
     * - 201 (empty response) if the row is succesfully added to the model.
     *      If possible a Link header will be supplied to the new record
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return EmptyResponse|JsonResponse
     */
    public function post(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($this->checkContentType($request) === false) {
            return new EmptyResponse(415);
        }

        $parsedBody = json_decode($request->getBody()->getContents(), true);

        if (empty($parsedBody)) {
            return new EmptyResponse(400);
        }

        $row = $this->translateRow($parsedBody, true);

        return $this->saveRow($request, $row, false);
    }

    /**
     * Update a row in the model. Only needs the changed values in the model.
     *
     * Will return status:
     * - 404 when the model ID supplied in the request url is not found
     * - 415 when the content type of the data supplied in the request is not allowed
     * - 400 (empty response) if the row is empty or if the model could not save the row AFTER validation
     * - 400 (json response) if the row did not pass validation. Errors will be returned in the body
     * - 201 (empty response) if the row is succesfully added to the model.
     *      If possible a Link header will be supplied to the new record
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return EmptyResponse
     */
    public function patch(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $this->getId($request);

        $idField = $this->getIdField();
        if ($id === null || !$idField) {
            return new EmptyResponse(404);
        }

        if ($this->checkContentType($request) === false) {
            return new EmptyResponse(415);
        }

        $parsedBody = json_decode($request->getBody()->getContents(), true);
        $newRowData = $this->translateRow($parsedBody, true);

        $filter = $this->getIdFilter($id, $idField);

        $row = $this->model->loadFirst($filter);

        $row = $newRowData + $row;

        return $this->saveRow($request, $row, true);
    }

    /**
     *
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return \Psr\Http\Message\ResponseInterface|EmptyResponse
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $this->getUserAtributesFromRequest($request);
        $this->addCurrentUserToModel();

        $this->model = $this->createModel();
        if (!$this->model instanceof \MUtil_Model_ModelAbstract) {
            throw new \Exception('No valid model loaded');
        }
        if (method_exists($this->model, 'applyApiSettings')) {
            $this->model->applyApiSettings();
        }

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

        $this->logRequest($request, $row, false);
        $row = $this->beforeSaveRow($row);

        $modelProcessor = new ModelProcessor($this->loader, $this->model, $this->userId);
        if ($update === false) {
            $modelProcessor->setAddDefaults(true);
        }

        try {
            $newRow = $modelProcessor->save($row, $update);
        } catch(\Exception $e) {
            // Row could not be saved.
            // return JsonResponse

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
            } catch(\Zend\Expressive\Router\Exception\InvalidArgumentException $e) {
                // Give it another go for custom routes
                $getRouteName = join('.', $routeParts);
                try {
                    $location = $this->helper->generate($getRouteName, $routeParams);
                } catch(\Zend\Expressive\Router\Exception\InvalidArgumentException $e) {
                    $location = null;
                }
            }
            if ($location !== null) {
                return new EmptyResponse(
                    201,
                    [
                        'Location' => $location,
                    ]
                );
            }
        }

        return new EmptyResponse(201);
    }

    /**
     * Set the dateformat if none is supplied in the current model. Otherwise dates will not be transformed to \MUtil_Date
     * Also removes the timezone from the date, as \MUtil_Date does not understand it with timezone.
     *
     * @param $row
     * @return mixed
     */
    protected function setModelDates($row)
    {
        foreach($row as $columnName=>$value) {
            $type = $this->model->get($columnName, 'type');
            if ($type === \MUtil_Model::TYPE_DATETIME || $type === \MUtil_Model::TYPE_DATE) {
                //if ($this->model->get($columnName, 'dateFormat') === null) {
                //    $this->model->set($columnName, 'dateFormat', \MUtil_Date::ISO_8601);
                //}

                if (strpos($value, '+') === 19 || strpos($value, '.') === 19) {
                    $value = substr($value, 0, 19);
                }
                $row[$columnName] = new \MUtil_Date($value, \MUtil_Date::ISO_8601);
            }

        }
        return $row;
    }

    /**
     * Get the structural information of each model field so it will be easier to see what information is
     * received or needed for a POST/PATCH
     *
     * @return JsonResponse
     * @throws \Zend_Date_Exception
     */
    public function structure()
    {
        $structure = $this->getStructure();
        return new JsonResponse($structure);
    }

    /**
     * Translate a row with the api names and a date transformation to ISO 8601
     *
     * @param $row
     * @param bool $reversed
     * @return array
     */
    public function translateRow($row, $reversed=false)
    {
        $translations = $this->getApiNames($reversed);

        $translatedRow = $this->translateList($row, $translations);

        return $translatedRow;
    }

    public function translateList($row, $translations)
    {
        $translatedRow = [];
        foreach($row as $colName=>$value) {

            if (is_array($value) && isset($translations[$colName]) && is_array($translations[$colName])) {
                foreach($value as $key=>$subrow) {
                    $translatedRow[$colName][$key] = $this->translateList($subrow, $translations[$colName]);
                }
                continue;
            }

            if ($value instanceof \MUtil_Date) {
                $value = $value->toString(\MUtil_Date::ISO_8601);
            }

            if ($value instanceof \DateTime) {
                $value = $value->format(\DateTime::ATOM);
            }

            if (isset($translations[$colName])) {
                $translatedRow[$translations[$colName]] = $value;
            } else {
                $translatedRow[$colName] = $value;
            }
        }

        return $translatedRow;
    }
}
