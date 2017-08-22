<?php


namespace Gems\Rest\Action;

use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Exception;

abstract class ModelRestControllerAbstract extends RestControllerAbstract
{
    protected $apiNames;

    protected $errors;

    protected $itemsPerPage = 25;

    protected $reverseApiNames;

    protected $structure;

    protected $validators;

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
            $this->model->delete($filter);
        } catch (Exception $e) {
            return new EmptyResponse(400);
        }

        return new EmptyResponse(204);
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $request->getAttribute('id');
        if ($id !== null) {
            $idField = $this->getIdField();
            if ($idField) {
                $filter = [
                    $idField => $id,
                ];
                $row = $this->model->loadFirst($filter);
                $row = $this->translateRow($row);
                return new JsonResponse($row);
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

    protected function getIdField()
    {
        $keys = $this->model->getKeys();
        if (isset($keys['id'])) {
            return $keys['id'];
        }
        return null;
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

            $colName = $key;
            if (isset($translations[$key])) {
                $colName = $translations[$key];
            }

            if (isset($itemNames[$colName])) {
                $filters[$colName] = $value;
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
                $name = $orderParam;
                $sort = false;
                if (strpos(strtolower($orderParam), ' desc')) {
                    $name = substr($orderParam, 0,-5);
                    $sort = SORT_DESC;
                }
                if (strpos(strtolower($orderParam), ' asc')) {
                    $name = substr($orderParam, 0,-4);
                    $sort = SORT_ASC;
                }

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
        $row = $this->translateRow($request->getParsedBody(), true);
        return $this->saveRow($row);
    }

    public function patch(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $parsedBody = json_decode($request->getBody()->getContents(), true);
        $newRowData = $this->translateRow($parsedBody, true);

        $id = $request->getAttribute('id');
        $idField = $this->getIdField();
        if ($id === null || !$idField) {
            return new EmptyResponse(404);
        }

        $filter = [
            $idField => $id,
        ];
        $row = $this->model->loadFirst($filter);

        $row = $newRowData + $row;

        return $this->saveRow($row);
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $this->model = $this->createModel();
        if (method_exists($this->model, 'applyApiSettings')) {
            $this->model->applyApiSettings();
        }

        return parent::process($request, $delegate);
    }

    public function saveRow($row)
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
        if (isset($newRow[$idField])) {
            $id = $newRow[$idField];
            return new EmptyResponse(
                201,
                [
                    'Location' => $this->helper->generate('api.organization.get', ['id' => $id]),
                ]
            );
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

    public function getValidator($validator)
    {
        if ($validator instanceof \Zend_Validate_Interface) {
            return $validator;
        } elseif (is_string($validator)) {
            $validator = $this->loader->create('Validate_'.$validator);
            if ($validator) {
                return $validator;
            } else {
                throw new Exception('Invalid validator provided to addValidator; must be string or Zend_Validate_Interface');
            }
        } else {
            throw new Exception(
                sprintf(
                    'Invalid validator provided to addValidator; must be string or Zend_Validate_Interface. Supplied %s',
                    $validator
                )
            );
        }
    }

    public function getValidators()
    {
        if (!$this->validators) {
            $multiValidators = $this->model->getCol('validators');
            $singleValidators = $this->model->getCol('validator');
            $requiredFields = $this->model->getCol('required');

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