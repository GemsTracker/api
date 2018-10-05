<?php


namespace Gems\Rest\Action;

use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Zalt\Loader\ProjectOverloader;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Exception;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Router\RouteResult;

abstract class ModelRestControllerAbstract extends RestControllerAbstract
{
    /**
     * @var array List of allowed content types as input for write methods
     */
    protected $allowedContentTypes = ['application/json'];

    /**
     * @var array list of translated colnames for the api
     */
    protected $apiNames;

    /**
     * @var db1 \Zend_Db_Adapter_Abstract
     */
    protected $db1;

    /**
     * @var List of errors from validating a row
     */
    protected $errors;

    /**
     * @var Fieldname of model that identifies a row with a unique ID
     */
    protected $idField;

    /**
     * @var int number of items per page for pagination
     */
    protected $itemsPerPage = 25;

    /**
     * @var array list of methods supported by this current controller
     */
    protected $supportedMethods = [
        'delete', 'get', 'options', 'patch', 'post', 'structure',
    ];

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
    public function __construct(ProjectOverloader $loader, UrlHelper $urlHelper, $LegacyDb)
    {
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
     * @param $row Row with model values
     * @param bool $save Will the row be saved after filter (enables readonly
     * @param bool $useKeys Use keys or values in the filter of the row
     * @return array Filtered array
     */
    protected function filterColumns($row, $save=false, $useKeys=true)
    {
        $flag = ARRAY_FILTER_USE_KEY;
        if ($useKeys === false) {
            $flag = ARRAY_FILTER_USE_BOTH;
        }

        if (isset($this->routeOptions['allowed_fields'])) {
            $allowedFields = $this->routeOptions['allowed_fields'];

            $row = array_filter($row, function ($key) use ($allowedFields) {
                return in_array($key, $allowedFields);
            }, $flag);
        }

        if (isset($this->routeOptions['disallowed_fields'])) {
            $disallowedFields = $this->routeOptions['disallowed_fields'];

            $row = array_filter($row, function ($key) use ($disallowedFields) {
                return !in_array($key, $disallowedFields);
            }, $flag);

        }

        if ($save && isset($this->routeOptions['readonly_fields'])) {
            $readonlyFields = $this->routeOptions['readonly_fields'];

            $row = array_filter($row, function ($key) use ($readonlyFields) {
                return !in_array($key, $readonlyFields);
            }, $flag);

        }

        return $row;
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
            $idField = $this->getIdField();
            if ($idField) {
                $filter = $this->getIdFilter($id, $idField);

                $row = $this->model->loadFirst($filter);
                if (is_array($row)) {
                    $row = $this->translateRow($row);
                    $row = $this->filterColumns($row);
                    return new JsonResponse($row);
                }
            }
            return new EmptyResponse(404);
        } else {
            return $this->getList($request, $delegate);
        }
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
            $this->apiNames = $this->model->getCol('apiName');
        }

        if ($reverse) {
            if (!$this->reverseApiNames) {
                $this->reverseApiNames = array_flip($this->apiNames);
            }
            return $this->reverseApiNames;
        }

        return $this->apiNames;
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
     * @return Fieldname
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
        if (is_array($id) && is_array($idField)) {
            $filter = [];
            foreach($idField as $key=>$singleField) {
                $filter[$singleField] = $id[$key];
            }
        } else {
            $filter = [
                $idField => $id,
            ];
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

        $itemNames = array_flip($this->model->getItemNames());
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
                $filters[] = $field . ' LIKE '. $this->db1->quote('%'.$separator . $value . $separator . '%');
                continue;
            }

            $colName = $key;
            if (isset($translations[$key])) {
                $colName = $translations[$key];
            }
            
            if (isset($itemNames[$colName])) {
                if (is_string($value)) {
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
                }
            }
        }

        return $filters;
    }

    /**
     * Get the order items should be ordered in for listing model items with a GET request
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    public function getListOrder(ServerRequestInterface $request)
    {
        $params = $request->getQueryParams();
        if (isset($params['order'])) {
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

            $routeResult = $request->getAttribute('Zend\Expressive\Router\RouteResult');
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
            $columns = $this->model->getItemNames();

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
            ];

            $structure = [];

            $columns = $this->filterColumns($columns, false, false);

            foreach ($columns as $columnName) {

                $columnLabel = $columnName;
                if (isset($translations[$columnName])) {
                    $columnLabel = $translations[$columnName];
                }

                foreach ($structureAttributes as $attributeName) {
                    if ($this->model->has($columnName, $attributeName)) {

                        $structure[$columnLabel][$attributeName] = $this->model->get($columnName, $attributeName);

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
            $this->structure = $structure;
        }

        return $this->structure;
    }

    /**
     * Get a specific validator to be run during validation
     *
     * @param $validator
     * @param null $options
     * @return object
     * @throws \Zalt\Loader\Exception\LoadException
     */
    public function getValidator($validator, $options=null)
    {
        if ($validator instanceof \Zend_Validate_Interface) {
            return $validator;
        } elseif (is_string($validator)) {
            $validatorName = $validator;
            if ($options !== null) {
                $validator = $this->loader->create('Validate_' . $validator, $options);
            } else {
                $validator = $this->loader->create('Validate_'.$validator);
            }

            if ($validator) {
                return $validator;
            } else {
                throw new Exception(sprintf('Validator %s not found', $validatorName));
            }
        } else {
            throw new Exception(
                sprintf(
                    'Invalid validator provided to addValidator; must be string or Zend_Validate_Interface. Supplied %s',
                    gettype($validator)
                )
            );
        }
    }

    /**
     * Get the validators for each of the columns in the model
     * This function will also create required validators and type validators for rows that are required.
     * If a POST method is used, the key values will be excluded
     *
     * @return array
     * @throws \Zalt\Loader\Exception\LoadException
     */
    public function getValidators()
    {
        if (!$this->validators) {

            if ($this->model instanceof \MUtil_Model_JoinModel && method_exists($this->model, 'getSaveTables')) {
                $saveableTables = $this->model->getSaveTables();

                $multiValidators = [];
                $singleValidators = [];
                $allRequiredFields = [];
                $types = [];

                foreach($this->model->getCol('table') as $colName=>$table) {
                    if (isset($saveableTables[$table])) {
                        $columnValidators = $this->model->get($colName, 'validators');
                        if ($columnValidators !== null) {
                            $multiValidators[$colName] = $columnValidators;
                        }
                        $columnValidator = $this->model->get($colName, 'validator');
                        if ($columnValidator) {
                            $singleValidators[$colName] = $columnValidator;
                        }
                        $columnRequired = $this->model->get($colName, 'required');
                        if ($columnRequired === true) {
                            if ($this->method != 'post' || $this->model->get($colName, 'key') !== true) {
                                $allRequiredFields[$colName] = $columnRequired;
                            }
                        }
                        if ($columnType = $this->model->get($colName, 'type')) {
                            $types[$colName] = $this->model->get($colName, 'type');
                        }
                    }
                }
            } else {
                $multiValidators = $this->model->getCol('validators');
                $singleValidators = $this->model->getCol('validator');
                $allRequiredFields = $this->model->getCol('required');
                $types = $this->model->getCol('type');
            }



            $requiredFields = $allRequiredFields;
            /*foreach($labeledFields  as $labeledField) {
                if (isset($allRequiredFields[$labeledField])) {
                    $requiredFields[$labeledField] = $allRequiredFields[$labeledField];
                }
            }*/

            $this->requiredFields = $requiredFields;

            foreach($multiValidators as $columnName=>$validators) {
                foreach($validators as $key=>$validator) {

                    $multiValidators[$columnName][$key] = $this->getValidator($validator);
                }
            }

            foreach($singleValidators as $columnName=>$validator) {
                $multiValidators[$columnName][] = $this->getValidator($validator);
            }

            foreach($requiredFields as $columnName=>$required) {

                if ($required && $this->model->get($columnName, 'autoInsertNotEmptyValidator') !== false) {
                    $multiValidators[$columnName][] = $this->getValidator('NotEmpty');

                } else {
                    $this->requiredFields[$columnName] = false;
                }

                if (!isset($multiValidators[$columnName]) || count($multiValidators[$columnName]) === 1) {
                    switch ($types[$columnName]) {
                        case \MUtil_Model::TYPE_STRING:
                            //$multiValidators[$columnName][] = $this->getValidator('Alnum', ['allowWhiteSpace' => true]);
                            break;

                        case \MUtil_Model::TYPE_NUMERIC:
                            $multiValidators[$columnName][] = $this->getValidator('Float');
                            break;

                        case \MUtil_Model::TYPE_DATE:
                            $multiValidators[$columnName][] = $this->getValidator('Date');
                            break;

                        case \MUtil_Model::TYPE_DATETIME:
                            $multiValidators[$columnName][] = $this->getValidator('Date', ['format' => \Zend_Date::ISO_8601]);
                            break;
                    }
                }
            }

            $this->validators = $multiValidators;
        }

        return $this->validators;
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
     * @return EmptyResponse
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

        $row = $this->translatePostRow($parsedBody);

        return $this->saveRow($request, $row);
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

        $newRowData = $this->setModelDates($newRowData);

        $filter = $this->getIdFilter($id, $idField);

        $row = $this->model->loadFirst($filter);

        $row = $newRowData + $row;

        //$row = $this->addChangeFields($row);

        return $this->saveRow($request, $row);
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
     * @return EmptyResponse
     */
    public function saveRow(ServerRequestInterface $request, $row)
    {
        if (empty($row)) {
            return new EmptyResponse(400);
        }

        $row = $this->filterColumns($row, true);

        $row = $this->beforeSaveRow($row);

        try {
            $this->validateRow($row);
        } catch (Exception $e) {
            return new JsonResponse($this->errors, 400);
        }

        try {
            $newRow = $this->model->save($row);
        } catch (Exception $e) {
            return new EmptyResponse(400);
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
            $routeParams['id'] = $newRow[$idField];
        }

        if (!empty($routeParams)) {

            $result = $request->getAttribute(RouteResult::class);
            $routeName = $result->getMatchedRouteName();

            $routeParts = explode('.', $routeName);
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
                if ($this->model->get($columnName, 'dateFormat') === null) {
                    $this->model->set($columnName, 'dateFormat', \MUtil_Date::ISO_8601);
                }

                if (strpos($value, '+') === 19 || strpos($value, '.') === 19) {
                    $row[$columnName] = substr($value, 0, 19);
                }
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
     * Translate a row for a POST request.
     * 1. Translate
     * 2. Add default values
     * 3. Add changed and created Field values
     *
     * @param $row
     * @return array|mixed
     */
    protected function translatePostRow($row)
    {
        $row = $this->translateRow($row, true);
        $row = $this->setModelDates($row);

        if (!empty($row)) {
            $row = $this->addNewModelRow($row);
            //$row = $this->addChangeFields($row);
            //$row = $this->addCreateFields($row);
        }

        return $row;
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
        $translations = $this->getApiNames();

        if ($reversed) {
            $translations = $this->getApiNames($reversed);
        }

        $translatedRow = [];
        foreach($row as $colName=>$value) {

            if ($value instanceof \MUtil_Date) {
                $value = $value->toString(\MUtil_Date::ISO_8601);
            }

            if (isset($translations[$colName])) {
                $translatedRow[$translations[$colName]] = $value;
            } else {
                $translatedRow[$colName] = $value;
            }
        }

        return $translatedRow;
    }

    /**
     * Validate a row before saving it to the model and store the errors in $this->errors
     *
     * @param $row
     * @throws \Zalt\Loader\Exception\LoadException
     */
    public function validateRow($row)
    {
        $rowValidators = $this->getValidators();
        $translations = $this->getApiNames();
        $idField = $this->getIdField();

        // No ID field is needed when it's a POST and a single array
        if ($this->method == 'post' && !is_array($idField) && isset($rowValidators[$idField])) {
            unset($rowValidators[$idField]);
        }

        foreach ($rowValidators as $colName=>$validators) {
            $value = null;
            if (isset($row[$colName])) {
                $value = $row[$colName];
            }

            if (
                (null === $value || '' === $value) &&
                (!$this->requiredFields || !isset($this->requiredFields[$colName]) || !$this->requiredFields[$colName])
            ) {
                continue;
            }

            $translatedColName = $colName;
            if (isset($translations[$colName])) {
                $translatedColName = $translations[$colName];
            }
            foreach($validators as $validator) {
                if (!$validator->isValid($value, $row)) {
                    if (!isset($this->errors[$translatedColName])) {
                        $this->errors[$translatedColName] = [];
                    }
                    $this->errors[$translatedColName] += $validator->getMessages();//array_merge($this->errors[$colName], $validator->getMessages());
                }
            }
        }

        if ($this->errors) {
            throw new Exception('Validation Errors');
        }
    }
}