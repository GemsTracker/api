<?php


namespace Gems\Rest;


use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Auth\AuthorizeGemsAndOauthMiddleware;

abstract class RestModelConfigProviderAbstract
{
    public function getMiddleware()
    {
        return [
            AuthorizeGemsAndOauthMiddleware::class,
            ModelRestController::class
        ];
    }

    protected function getModelRoutes()
    {
        $restModels = $this->getRestModels();

        $routes = [];

        foreach($restModels as $endpoint=>$settings) {

            $methods = array_flip($settings['methods']);
            $idRegex = '\d+';
            if (isset($settings['idFieldRegex'])) {
                $idRegex = $settings['idFieldRegex'];
            }

            if (!empty($methods)) {
                $routes[] = [
                    'name' => 'api.' . $endpoint . '.structure',
                    'path' => '/' . $endpoint . '/structure',
                    'middleware' => $this->getMiddleware(),
                    'options' => $settings,
                    'allowed_methods' => ['GET']
                ];
            }

            if (isset($methods['GET'])) {
                $routes[] = [
                    'name' => 'api.' . $endpoint . '.get',
                    'path' => '/' . $endpoint . '[/{id:' . $idRegex . '}]',
                    'middleware' => $this->getMiddleware(),
                    'options' => $settings,
                    'allowed_methods' => ['GET']
                ];
            }

            if (isset($methods['POST'])) {
                $routes[] = [
                    'name' => 'api.' . $endpoint . '.post',
                    'path' => '/' . $endpoint,
                    'middleware' => $this->getMiddleware(),
                    'options' => $settings,
                    'allowed_methods' => ['POST']
                ];
            }

            if (isset($methods['PATCH'])) {
                $routes[] = [
                    'name' => 'api.' . $endpoint . '.patch',
                    'path' => '/' . $endpoint . '/[{id:' . $idRegex . '}]',
                    'middleware' => $this->getMiddleware(),
                    'options' => $settings,
                    'allowed_methods' => ['PATCH']
                ];
            }

            if (isset($methods['DELETE'])) {
                $routes[] = [
                    'name' => 'api.' . $endpoint . '.delete',
                    'path' => '/' . $endpoint . '/[{id:' . $idRegex . '}]',
                    'middleware' => $this->getMiddleware(),
                    'options' => $settings,
                    'allowed_methods' => ['DELETE']
                ];
            }
        }

        return $routes;
    }

    abstract protected function getRestModels();

    protected function getRoutes()
    {
        return $this->getModelRoutes();
    }
}