<?php


namespace Gems\Rest\Repository;


use FastRoute\RouteParser\Std;
use Gems\Rest\Model\ModelStructureRepository;
use Gems\Rest\Model\RouteOptionsModelFilter;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Loader\ProjectOverloader;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class ApiDefinitionRepository
{


    protected $config;

    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @var ProjectOverloader
     */
    private $loader;

    protected $openApiVersion = '3.0.2';

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    protected $structures = [];


    public function __construct(Adapter $db, $config, ProjectOverloader $loader, $LegacyDb)
    {
        $this->config = $config;
        $this->db = $db;
        $this->loader = $loader;
    }

    public function getDefinition(ServerRequestInterface $request, $role)
    {
        $this->request = $request;
        $header = $this->getHeader();

        $paths = $this->getPaths($role);

        $security = $this->getSecuritySchemes($role);

        $definition = [];

        $definition += $header;
        $definition += $paths;
        $definition = array_merge_recursive($definition, $security);

        return $definition;
    }

    protected function getHeader()
    {
        $title = 'API';
        if (isset($this->config['api'], $this->config['api']['info'], $this->config['api']['info']['name'])) {
            $title = $this->config['api']['info']['name'];
        }

        $uri = $this->request->getUri();
        $url = $uri->getScheme() . '://' . $uri->getHost();

        $header = [
            'openapi' => $this->openApiVersion,
            'info' => [
                'title' => $title,
                'version' => '1', // Default version as version is required
            ],
            'servers' => [
                ['url' => $url],
            ],

        ];

        if (isset($this->config['api'], $this->config['api']['info'], $this->config['api']['info']['description'])) {
            $header['info']['description'] = $this->config['api']['info']['description'];
        }
        if (isset($this->config['api'], $this->config['api']['info'], $this->config['api']['info']['version'])) {
            $header['info']['version'] = (string)$this->config['api']['info']['version'];
        }

        return $header;
    }

    protected function getPaths($role)
    {
        $permissions = $this->getPermissionsForRole($role);
        $modelRoutes = $this->getModelRoutes();
        $customRoutes = $this->getCustomRoutes();

        $paths = [];
        $components['schemas'] = [];
        foreach ($permissions as $permission => $methods) {
            $modelRoute = null;
            foreach ($modelRoutes as $routeName => $route) {
                if ($routeName == $permission || strpos('api.' . $routeName, $permission) === 0) {
                    $route['path'] = $routeName;
                    $modelRoute = $route;
                    break;
                }
            }

            if ($modelRoute) {
                $prefixedPath = $modelRoute['path'];
                if (strpos($prefixedPath, '/') !== 0) {
                    $prefixedPath = '/' . $prefixedPath;
                }

                if (!isset($paths[$prefixedPath])) {
                    $paths[$prefixedPath] = [];
                }
                $idParam = '/{id}';
                if (isset($modelRoute['idField'])) {
                    if (is_array($modelRoute['idField'])) {
                        $idParam = '';
                        foreach ($modelRoute['idField'] as $field) {
                            $idParam .= '/{' . $field . '}';
                        }
                    } else {
                        $idParam = '{' . $modelRoute['idField'] . '}';
                    }
                }

                $modelRoute['modelSimpleName'] = str_replace(['\\', '_'], '', $modelRoute['model']);


                if (in_array('GET', $modelRoute['methods'])) {
                    $paths[$prefixedPath]['get'] = $this->getModelRouteInfo('GETLIST', $modelRoute);
                    if (!isset($paths[$prefixedPath . $idParam])) {
                        $paths[$prefixedPath . $idParam] = [];
                    }
                    $paths[$prefixedPath . $idParam]['get'] = $this->getModelRouteInfo('GET', $modelRoute);

                    $components['schemas'][$modelRoute['modelSimpleName']] = $this->getModelRouteSchemas('GET', $modelRoute);
                    $components['schemas'][$modelRoute['modelSimpleName'] . 's'] = $this->getModelRouteSchemas('GETLIST', $modelRoute);


                }
                if (in_array('POST', $modelRoute['methods'])) {
                    $paths[$prefixedPath]['post'] = $this->getModelRouteInfo('POST', $modelRoute);
                    $components['schemas']['new' . $modelRoute['modelSimpleName']] = $this->getModelRouteSchemas('POST', $modelRoute);
                }
                if (in_array('PATCH', $modelRoute['methods'])) {
                    if (!isset($paths[$prefixedPath . $idParam])) {
                        $paths[$prefixedPath . $idParam] = [];
                    }
                    $paths[$prefixedPath . $idParam]['patch'] = $this->getModelRouteInfo('PATCH', $modelRoute);
                    $components['schemas']['new' . $modelRoute['modelSimpleName']] = $this->getModelRouteSchemas('POST', $modelRoute);
                }
                if (in_array('DELETE', $modelRoute['methods'])) {
                    if (!isset($paths[$prefixedPath . $idParam])) {
                        $paths[$prefixedPath . $idParam] = [];
                    }
                    $paths[$prefixedPath . $idParam]['delete'] = $this->getModelRouteInfo('DELETE', $modelRoute);
                }
            } else {
                $customRoute = null;
                foreach ($customRoutes as $route) {
                    if ($route['name'] == $permission || strpos($route['name'] . '/[{', $permission) === 0) {
                        $customRoute = $route;
                        break;
                    }
                }
                if ($customRoute) {
                    $controller = end($customRoute['middleware']);

                    if (property_exists($controller, 'definition')) {
                        $definition = $controller::$definition;
                        $path = $customRoute['path'];
                        $routeParser = new Std();
                        $parsedRoute = $routeParser->parse($path);
                        $allParts = end($parsedRoute);
                        $translatedPath = '';
                        foreach($allParts as $part) {
                            if (is_array($part)) {
                                $translatedPath .= '{'.$part[0].'}';
                                continue;
                            }
                            $translatedPath .= $part;
                        }

                        foreach ($definition['methods'] as $method => $def) {
                            switch ($method) {
                                case 'getlist':
                                    $paths[$translatedPath]['get'] = $this->getCustomRouteInfo($method, $customRoute, $definition);
                                    break;
                                case 'post':

                                    $paths[$translatedPath]['post'] = $this->getCustomRouteInfo($method, $customRoute, $definition);
                                    break;
                                case 'get':
                                case 'patch':
                                case 'delete':
                                    $paths[$translatedPath][$method] = $this->getCustomRouteInfo($method, $customRoute, $definition);
                                    break;
                            }
                        }
                    }
                }
            }
        }

        $body = ['paths' => $paths];
        if (count($components['schemas']) > 0) {
            $body['components'] = $components;
        }

        return $body;
    }

    protected function getCustomRouteInfo($method, $customRoute, $definition)
    {
        $namedMethod = $method;
        if ($namedMethod == 'getlist') {
            $namedMethod = 'get';
        }
        $summary = $namedMethod;
        $responses = null;
        $parameters = null;
        $requestBody = null;

        $pathMethods = ['get', 'patch', 'delete'];
        $pathMethod = false;
        if (in_array($method, $pathMethods)) {
            $pathMethod = true;
        }

        if (array_key_exists('topic', $definition)) {
            $topic = $definition['topic'];
            if ($method == 'getlist') {
                $summary .= ' all';
            }
            $summary .= ' ' . $topic;
        }

        $methodDefinition = $definition['methods'][$method];
        if (array_key_exists('params', $methodDefinition)) {
            $params = $methodDefinition['params'];
            foreach ($params as $param => $paramSettings) {
                $parameter = [
                    'name' => $param,
                    'in' => 'query',
                    'required' => false,
                    'schema' => [
                        'type' => 'string',
                    ],
                ];

                if ($pathMethod && !(array_key_exists('in', $paramSettings) && $paramSettings['in'] == 'query')) {
                    $parameter['in'] = 'path';
                }

                if (array_key_exists('required', $paramSettings)) {
                    $parameter['required'] = $paramSettings['required'];
                }

                if (array_key_exists('type', $paramSettings)) {
                    if (!is_array($paramSettings['type'])) {
                        switch ($paramSettings['type']) {
                            case 'string':
                                if (isset($paramSettings['maxlength'])) {
                                    $parameter['schema']['maxLength'] = (int)$paramSettings['maxlength'];
                                }
                                break;
                            case 'int':
                                $parameter['schema']['type'] = 'integer';
                                $parameter['schema']['format'] = 'int64';
                                break;
                            case 'date':
                                $parameter['schema']['format'] = 'date';
                                break;
                            case 'datetime':
                                $parameter['schema']['format'] = 'date-time';
                                break;
                            case 'boolean':
                                $parameter['schema']['type'] = 'boolean';
                                break;
                        }
                    }
                }
                $parameters[] = $parameter;
            }
        }

        if (array_key_exists('responses', $methodDefinition)) {
            foreach ($methodDefinition['responses'] as $code => $responseInfo) {
                $response = [
                    'description' => $summary,
                ];
                if (is_array($responseInfo)) {
                    $response['content']['application/json']['schema'] = $this->getSchemaFromSimpleDef($responseInfo);
                } else {
                    $response['description'] = $responseInfo;
                }
                $responses[$code] = $response;
            }
        }

        if (array_key_exists('body', $methodDefinition)) {

            if (is_array($methodDefinition['body'])) {
                $requestBody = [
                    'description' => $summary,
                ];
                $requestBody['content']['application/json']['schema'] = $this->getSchemaFromSimpleDef($methodDefinition['body']);
            }
        }

        $info = [
            'summary' => $summary,
            'tags' => ['Other'],
        ];
        if ($parameters) {
            $info['parameters'] = $parameters;
        }
        if ($requestBody) {
            $info['requestBody'] = $requestBody;
        }
        if ($responses) {
            $info['responses'] = $responses;
        }

        return $info;
    }

    protected function getModelRouteInfo($method, $routeSettings)
    {
        $namedMethod = $method;
        if ($namedMethod == 'GETLIST') {
            $namedMethod = 'GET';
        }

        $modelSimpleName = $routeSettings['modelSimpleName'];

        $structure = $this->getModelStructure($routeSettings);

        $urlParams = [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer', 'format' => 'int64']]];
        if (isset($routeSettings['idField'])) {
            $urlParams = [];
            $idFields = $routeSettings['idField'];
            if (!is_array($idFields)) {
                $idFields = [$routeSettings['idField']];
            }


            $filteredStructure = array_intersect_key($structure, array_flip($idFields));
            $idSchema = $this->createSchemaFromStructure($filteredStructure);
            $properties = $idSchema['properties'];


            foreach ($idFields as $field) {
                $idFieldSchema = ['type' => 'integer', 'format' => 'int64'];
                if (isset($properties[$field])) {
                    $idFieldSchema = $properties[$field];
                }
                $urlParams[] = ['name' => $field, 'in' => 'path', 'required' => true, 'schema' => $idFieldSchema];
            }
        }

        $listStructure = RouteOptionsModelFilter::filterColumns($structure, $routeSettings, false);
        $listSchema = $this->createSchemaFromStructure($listStructure);
        $listProperties = [];
        foreach ($listSchema['properties'] as $fieldName => $schema) {
            $description = null;
            if (array_key_exists('description', $schema)) {
                $description = $schema['description'];
                unset($schema['description']);
            }
            $property = [
                'name' => $fieldName,
                'in' => 'query',
                'schema' => $schema,
            ];

            if ($description) {
                $property['description'] = $description;
            }

            $listProperties[] = $property;
        }

        $responses = null;
        $parameters = null;

        $requestBody = null;

        switch($method) {
            case 'GETLIST':
                $summary = 'List all ' . $routeSettings['path'];
                break;
            case 'GET':
                $summary = 'List one ' . $routeSettings['path'];
                break;
            case 'POST':
                $summary = 'Add a ' . $routeSettings['path'];
                break;
            case 'PATCH':
                $summary = 'Update a ' . $routeSettings['path'];
                break;
            case 'PATCH':
                $summary = 'Remove a ' . $routeSettings['path'];
                break;
            default:
                $summary = $namedMethod . '  for ' . $routeSettings['path'];
                break;
        }

        switch ($method) {

            case 'GETLIST':
                $responses = [
                    '200' => [
                        'description' => 'get items',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => "#/components/schemas/" . $modelSimpleName . 's',
                                ],
                            ],
                        ],
                    ],
                    '204' => [
                        'description' => 'no items',
                    ],
                ];
                $parameters = [
                    [
                        'name' => 'per_page',
                        'in' => 'query',
                        'description' => 'Items per page',
                        'schema' => [
                            'type' => 'integer',
                            'format' => 'int32',
                        ],
                    ],
                    [
                        'name' => 'page',
                        'in' => 'query',
                        'description' => 'Page number',
                        'schema' => [
                            'type' => 'integer',
                            'format' => 'int32',
                        ],
                    ],
                    [
                        'name' => 'order',
                        'in' => 'query',
                        'description' => 'Order items by. Comma separated for multiple values. Either end with " DESC" or prefixed with - for descending order',
                        'schema' => [
                            'type' => 'string',
                        ],
                    ]
                ];

                $parameters = array_merge($parameters, $listProperties);

                break;
            case 'GET':
                $responses = [
                    '200' => [
                        'description' => 'Get a specific item',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => "#/components/schemas/" . $modelSimpleName,
                                ],
                            ],
                        ],
                    ],
                    '404' => [
                        'description' => 'Item not found',
                    ],
                ];
                $parameters = $urlParams;


                break;
            case 'POST':
                $requestBody = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => "#/components/schemas/new" . $modelSimpleName,
                            ]
                        ]
                    ]
                ];
                $responses = [
                    '201' => [
                        'description' => 'Item created',
                    ],
                    '400' => [
                        'description' => 'No valid data supplied',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => [
                                        'error',
                                        'message',
                                    ],
                                    'properties' => [
                                        'error' => [
                                            'type' => 'string',
                                        ],
                                        'message' => [
                                            'type' => 'string',
                                        ],
                                        'errors' => [
                                            'type' => 'object',
                                        ],
                                    ],
                                ]
                            ]
                        ]
                    ],
                ];
                break;
            case 'PATCH':
                $requestBody = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => "#/components/schemas/new" . $modelSimpleName,
                            ]
                        ]
                    ]
                ];
                $responses = [
                    '201' => [
                        'description' => 'Item changed',
                    ],
                    '400' => [
                        'description' => 'No valid data supplied',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => [
                                        'error',
                                        'message',
                                    ],
                                    'properties' => [
                                        'error' => [
                                            'type' => 'string',
                                        ],
                                        'message' => [
                                            'type' => 'string',
                                        ],
                                        'errors' => [
                                            'type' => 'object',
                                        ],
                                    ],
                                ]
                            ]
                        ]
                    ],
                ];

                $parameters = $urlParams;
                break;

            case 'DELETE':
                $responses = [
                    '204' => [
                        'description' => 'Item deleted',
                    ],
                    '404' => [
                        'description' => 'Item not found',
                    ],

                    '400' => [
                        'description' => 'Item could not be deleted',
                    ],
                ];
                $parameters = $urlParams;
                break;
        }

        $info = [
            'summary' => $summary,
            'tags' => [$routeSettings['path']],
            //'security' => [['OAuth2' => 'all']],
        ];
        if ($parameters) {
            $info['parameters'] = $parameters;
        }
        if ($requestBody) {
            $info['requestBody'] = $requestBody;
        }
        if ($responses) {
            $info['responses'] = $responses;
        }

        return $info;
    }

    protected function getModelRouteSchemas($method, $routeSettings)
    {
        $structure = $this->getModelStructure($routeSettings);

        $schema = null;
        switch ($method) {
            case 'GET':

                $filteredStructure = RouteOptionsModelFilter::filterColumns($structure, $routeSettings, false);
                $schema = $this->createSchemaFromStructure($filteredStructure);

                break;
            case 'GETLIST':
                $schema = [
                    'type' => 'array',
                    'items' => ['$ref' => '#/components/schemas/' . $routeSettings['modelSimpleName']],
                ];

                break;
            case 'POST':
                $filteredStructure = RouteOptionsModelFilter::filterColumns($structure, $routeSettings, true);
                $schema = $this->createSchemaFromStructure($filteredStructure);
        }


        return $schema;
    }

    protected function createSchemaFromStructure($structure)
    {
        $required = [];
        $properties = [];
        foreach ($structure as $fieldName => $data) {
            if (isset($data['required']) && $data['required'] === true) {
                $required[] = $fieldName;
            }

            $type = 'string';
            $format = null;
            $maxLength = null;
            if (!array_key_exists('type', $data)) {
                continue;
            }
            switch ($data['type']) {
                case 'string':
                    if (isset($data['maxlength'])) {
                        $maxLength = (int)$data['maxlength'];
                    }
                    break;
                case 'numeric':
                    $type = 'integer';
                    $format = 'int64';
                    break;
                case 'date':
                    $format = 'date';
                    break;
                case 'datetime':
                    $format = 'date-time';
                    break;
            }

            $properties[$fieldName] = [
                'type' => $type,
            ];
            if ($format !== null) {
                $properties[$fieldName]['format'] = $format;
            }
            if ($maxLength !== null) {
                $properties[$fieldName]['maxLength'] = $maxLength;
            }
            if (array_key_exists('label', $data)) {
                $properties[$fieldName]['description'] = $data['label'];
            }
            if (array_key_exists('description', $data)) {
                $properties[$fieldName]['description'] = $data['description'];
            }
        }

        $schema = [];
        if (count($required) > 0) {
            $schema['required'] = $required;
        }
        $schema['properties'] = $properties;

        return $schema;
    }

    protected function getModelStructure($routeSettings)
    {
        if (!isset($this->structures[$routeSettings['model']])) {
            $model = $this->loader->create($routeSettings['model']);

            if (isset($routeSettings['applySettings'])) {
                foreach ($routeSettings['applySettings'] as $methodName) {
                    if (method_exists($model, $methodName)) {
                        $model->$methodName();
                    }
                }
            }

            $modelStructureRepository = new ModelStructureRepository($model);
            $this->structures[$routeSettings['model']] = $modelStructureRepository->getStructure();
        }

        return $this->structures[$routeSettings['model']];
    }

    protected function getPermissionsForRole($currentRole)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();

        $select->from('gems__api_permissions')
            ->where(['gapr_allowed' => 1, 'gapr_role' => $currentRole]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        $permissions = iterator_to_array($result);

        $groupedPermissions = [];
        foreach ($permissions as $permission) {
            if (!array_key_exists($permission['gapr_resource'], $groupedPermissions)) {
                $groupedPermissions[$permission['gapr_resource']] = [];
            }
            $groupedPermissions[$permission['gapr_resource']][] = $permission['gapr_permission'];
        }

        return $groupedPermissions;
    }

    protected function getModelRoutes()
    {
        $configs = [];
        if (isset($this->config['api'], $this->config['api']['config_providers'])) {
            $configs = $this->config['api']['config_providers'];
        }

        $modelRoutes = [];
        //$customRoutes = [];
        foreach ($configs as $configClass) {
            $config = new $configClass;
            $modelRoutes += $config->getRestModels();
            //$customRoutes += $config->getRoutes(false);
        }

        return $modelRoutes;
    }

    protected function getCustomRoutes()
    {
        $configs = [];
        if (isset($this->config['api'], $this->config['api']['config_providers'])) {
            $configs = $this->config['api']['config_providers'];
        }

        $customRoutes = [];
        foreach ($configs as $configClass) {
            $config = new $configClass;
            $customRoutes = array_merge($customRoutes, $config->getRoutes(false));
        }

        return $customRoutes;
    }

    protected function getSchemaFromSimpleDef($definition)
    {
        $schema = [
            'properties' => [],
            'required' => [],
        ];
        foreach($definition as $columnName=>$type) {
            if (strpos($columnName, '~') === 0) {
                $columnName = substr($columnName, 1);
            } else {
                $schema['required'] = [$columnName];
            }
            $schema['properties'][$columnName] = [
                'type' => 'string',
            ];
            switch ($type) {
                case 'string':
                    /*if (isset($paramSettings['maxlength'])) {
                        $parameter['maxLength'] = (int)$paramSettings['maxlength'];
                    }*/
                    break;
                case 'int':
                    $schema['properties'][$columnName]['type'] = 'integer';
                    $schema['properties'][$columnName]['format'] = 'int64';
                    break;
                case 'date':
                    $schema['properties'][$columnName]['format'] = 'date';
                    break;
                case 'datetime':
                    $schema['properties'][$columnName]['format'] = 'date-time';
                    break;
                case 'array':
                case 'object':
                case 'boolean':
                    $schema['properties'][$columnName]['type'] = $type;
                    break;
            }
        }

        return $schema;
    }

    public function getSecuritySchemes($role)
    {
        $schemes = [];

        $schemes['OAuth2'] = [
            'type' => 'oauth2',
            'description' => 'oauth 2 auth with [thephpleague/oauth2-server](https://oauth2.thephpleague.com/)',
            'flows' => [
                'password' => [
                    'tokenUrl' => '/access_token',
                    'refreshUrl' => '/access_token',
                    'scopes' => ['all' => 'all available resources'],
                ],
                'authorizationCode' => [
                    'authorizationUrl' => '/authorize',
                    'tokenUrl' => '/access_token',
                    'refreshUrl' => '/access_token',
                    'scopes' => ['all' => 'all available resources'],
                ],
                'implicit' => [
                    'authorizationUrl' => '/authorize',
                    'refreshUrl' => '/access_token',
                    'scopes' => ['all' => 'all available resources'],
                ]
            ]
        ];

        $security = [['OAuth2' => ['all']]];

        return [
            'security' => $security,
            'components' => [
                'securitySchemes' => $schemes,
            ],
        ];

    }

    public function getVersion()
    {
        return $this->openApiVersion;
    }
}
