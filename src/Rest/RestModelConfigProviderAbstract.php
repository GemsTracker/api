<?php


namespace Gems\Rest;


use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Auth\AuthorizeGemsAndOauthMiddleware;
use Gems\Rest\Middleware\ApiGateMiddleware;
use Gems\Rest\Middleware\ApiOrganizationGateMiddleware;
use Gems\Rest\Middleware\SecurityHeadersMiddleware;

abstract class RestModelConfigProviderAbstract
{
    /**
     * Get an array of default routing middleware for REST actions with a custom action instead of the default controller
     *
     * @param $customAction classname of the custom action
     * @return array
     */
    public function getCustomActionMiddleware($customAction)
    {
        $customActionMiddleware = $this->getMiddleware();
        array_pop($customActionMiddleware);
        array_push($customActionMiddleware, $customAction);

        return $customActionMiddleware;
    }

    /**
     * Get an array of default routing middleware for Rest actions
     *
     * @return array
     */
    public function getMiddleware()
    {
        return [
            AuthorizeGemsAndOauthMiddleware::class,
            ApiGateMiddleware::class,
            ApiOrganizationGateMiddleware::class,
            SecurityHeadersMiddleware::class,
            ModelRestController::class,
        ];
    }

    /**
     * get a list of routes generated from the rest models defined in getRestModels()
     *
     * @return array
     */
    protected function getModelRoutes()
    {
        $restModels = $this->getRestModels();

        $routes = [];

        foreach($restModels as $endpoint=>$settings) {

            $methods = array_flip($settings['methods']);
            $idField = 'id';
            $idRegex = '\d+';

            $middleware = $this->getMiddleware();
            if (isset($settings['customAction'])) {
                $middleware = $this->getCustomActionMiddleware($settings['customAction']);
            }

            if (isset($settings['idFieldRegex'])) {
                $idRegex = $settings['idFieldRegex'];
            }

            if (isset($settings['idField'])) {
                $idField = $settings['idField'];
            }
            
            if (is_array($idField) && count($idField) > 1) {
                $routeParameters = '';
                foreach($idField as $key=>$field) {
                    $routeParameters .= '/{'.$field.':'.$idRegex[$key].'}';
                }
            } else {
                $routeParameters = '/[{' . $idField . ':' . $idRegex . '}]';
            }

            if (!empty($methods)) {
                $routes[] = [
                    'name' => 'api.' . $endpoint . '.structure',
                    'path' => '/' . $endpoint . '/structure',
                    'middleware' => $middleware,
                    'options' => $settings,
                    'allowed_methods' => ['GET']
                ];
            }

            if (isset($methods['GET'])) {
                $routes[] = [
                    'name' => 'api.' . $endpoint . '.get',
                    'path' => '/' . $endpoint . '['.$routeParameters.']',
                    'middleware' => $middleware,
                    'options' => $settings,
                    'allowed_methods' => ['GET']
                ];
            }

            $defaultPathMethods = ['OPTIONS'];
            if (isset($methods['POST'])) {
                $defaultPathMethods[] = 'POST';
            }

            $routes[] = [
                'name' => 'api.' . $endpoint,
                'path' => '/' . $endpoint,
                'middleware' => $middleware,
                'options' => $settings,
                'allowed_methods' => $defaultPathMethods,
            ];

            $fixedRouteMethods = [];

            if (isset($methods['PATCH'])) {
                $fixedRouteMethods[] = 'PATCH';
            }
            if (isset($methods['DELETE'])) {
                $fixedRouteMethods[] = 'DELETE';
            }

            if (!empty($fixedRouteMethods)) {
                $routes[] = [
                    'name' => 'api.' . $endpoint . '.fixed',
                    'path' => '/' . $endpoint . $routeParameters,
                    'middleware' => $middleware,
                    'options' => $settings,
                    'allowed_methods' => $fixedRouteMethods,
                ];
            }
        }

        return $routes;
    }

    /**
     * Get a list of Gemstracker models that should be exposed to the REST API
     * the named keys of the array will be the endpoints.
     * each value is an array with the following required keys and values:
     *  model: (string)The name of the model for this endpoint as registered in the loader
     *  methods: (array with strings) supported methods (e.g. GET, POST, PATCH, DELETE)
     *
     * And the following optional keys and values:
     *  applySettings: (string) the name of the method that applies additional settings to the model (e.g. applyEditSettings)
     *  idField: (string|array) the name of the ID field as used in the url. Needed if it differs from the
     *      primary key of the model. Can also be multiple values if more than one id is needed
     *  idFieldRegex: (string|array) If the ID is not a number the default regex ('\d+') can be changed here.
     *      if multiple id values exist, and one has another regex, supply both regexes in the same order as the idFields
     *  multiOranizationField: (array) If the current model uses one column to store multiple organizations, it can be added here.
     *      supply the field key with the columnname, and the separator key with which separator char has been sewed together
     *
     *  Field filters: Keep in mind validation on save will occur after the filter.
     *  allow_fields: (array) List of fields that are allowed to be requested and saved
     *  disallow_fields: (array) List of fields that are not allowed to be requested and saved
     *  readonly_fields: (array) List of fields that are allowed to be requested, but not allowed to be saved
     *
     * @return array
     */
    abstract protected function getRestModels();

    /**
     * Get all Routed including model routes. Add your projects routes in this function of the configProvider of your project.
     *
     * @return array
     */
    protected function getRoutes()
    {
        return $this->getModelRoutes();
    }
}