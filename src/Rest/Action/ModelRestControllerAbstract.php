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
    protected $allowedContentTypes = ['application/json'];

    protected $apiNames;

    /**
     * @var db1 \Zend_Db_Adapter_Abstract
     */
    protected $db1;

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
     * @var \MUtil_Model_ModelAbstract Gemstracker Model
     */
    protected $model;

    protected $reverseApiNames;

    protected $structure;

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
                    return new JsonResponse($row);
                }
            }
            return new EmptyResponse(404);
        } else {
            return $this->getList($request, $delegate);
        }
    }

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

    protected function getId(ServerRequestInterface $request)
    {
        if (isset($this->routeOptions['idField'])) {
            if (is_array($this->routeOptions['idField'])) {
                $id = [];
                foreach($this->routeOptions['idField'] as $idField) {
                    $id[] = $request->getAttribute($idField);
                }
            } else {
                $id = $request->getAttribute($this->routeOptions['idField']);
            }

        } else {
            $id = $request->getAttribute('id');
        }

        return $id;
    }

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
            $translatedRows[$key] = $this->translateRow($row);
        }

        return new JsonResponse($translatedRows, 200, $headers);
    }

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
                if (strpos($value, '[') === 0 && strpos($value, ']') === strlen($value)-1) {
                    $values = explode(',', str_replace(['[', ']'], '', $value));
                    $firstValue = reset($values);
                    switch ($firstValue) {
                        case '<':
                        case '>':
                        case '<=':
                        case '>=':
                        case 'LIKE':
                            $secondValue = end($values);
                            if (is_numeric($secondValue)) {
                                $secondValue = ($secondValue == (int) $secondValue) ? (int) $secondValue : (float) $secondValue;
                            }
                            if ($firstValue == 'LIKE') {
                                $secondValue = $this->db1->quote($secondValue);
                            }
                            $filters[] = $colName . ' ' . $firstValue . ' ' . $secondValue;
                            break;
                        default:
                            $filters[$colName] = $values;
                            break;
                    }
                } else {
                    $filters[$colName] = $value;
                }
            }
        }

        return $filters;
    }

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

    public function post(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($this->checkContentType($request) === false) {
            return new EmptyResponse(415);
        }

        $row = $this->translateRow($request->getParsedBody(), true);
        return $this->saveRow($request, $row);
    }

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

        return $this->saveRow($request, $row);
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $this->model = $this->createModel();
        if (!$this->model instanceof \MUtil_Model_ModelAbstract) {
            throw new \Exception('No valid model loaded');
        }
        if (method_exists($this->model, 'applyApiSettings')) {
            $this->model->applyApiSettings();
        }

        return parent::process($request, $delegate);
    }

    public function saveRow(ServerRequestInterface $request, $row)
    {
        if (empty($row)) {
            return new EmptyResponse(400);
        }

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

        $idField = $this->getIdField();

        $routeParams = [];
        if (is_array($idField)) {


            foreach($idField as $key=>$singleField) {
                if (isset($newRow[$singleField])) {
                    $routeParams[$singleField] =     $newRow[$singleField];
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

            $routeParts = explode('.', $routeName);
            array_pop($routeParts);
            $getRouteName = join('.', $routeParts) . '.get';

            $location = $this->helper->generate($getRouteName, $routeParams);
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

    public function structure()
    {
        $structure = $this->getStructure();
        return new JsonResponse($structure);
    }

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

    public function getValidators()
    {
        if (!$this->validators) {
            $multiValidators = $this->model->getCol('validators');
            $singleValidators = $this->model->getCol('validator');
            $allRequiredFields = $this->model->getCol('required');
            $labeledFields = $this->model->getColNames('label');
            $types = $this->model->getCol('type');

            $requiredFields = [];
            foreach($labeledFields  as $labeledField) {
                if (isset($allRequiredFields[$labeledField])) {
                    $requiredFields[$labeledField] = $allRequiredFields[$labeledField];
                }
            }

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
                            $multiValidators[$columnName][] = $this->getValidator('Alnum', ['allowWhiteSpace' => true]);
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

    public function validateRow($row)
    {
        $rowValidators = $this->getValidators();
        $translations = $this->getApiNames();
        $idField = $this->getIdField();

        // No ID field is needed
        if ($this->method == 'post' && isset($rowValidators[$idField])) {
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

    abstract protected function createModel();

}