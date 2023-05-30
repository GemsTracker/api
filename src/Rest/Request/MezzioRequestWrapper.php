<?php

namespace Gems\Rest\Request;

use Psr\Http\Message\ServerRequestInterface;

class MezzioRequestWrapper extends \Zend_Controller_Request_Abstract
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var array route options
     */
    protected $routeOptions;

    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function getClientIp()
    {
        $server = $this->request->getServerParams();
        if (isset($server['REMOTE_ADDR'])) {
            return $server['REMOTE_ADDR'];
        }

        return null;
    }

    public function getParams()
    {
        $params = [
            'controller' => $this->getControllerName(),
            'action' => $this->getActionName(),
            'module' => 'default',
        ];

        $params += $this->request->getQueryParams();
        $params += $this->request->getParsedBody();

        return $params;
    }

    public function getParam($key, $default = null)
    {
        $params = $this->getParams();
        if (isset($params[$key])) {
            return $params[$key];
        }
        return null;
    }


    public function getActionName()
    {
        $action = $this->request->getAttribute('action');
        if ($action === null) {
            return 'index';
        }

        return $action;
    }

    public function getActionKey()
    {
        return 'action';
    }

    public function getControllerKey()
    {
        return 'controller';
    }

    public function getControllerName()
    {
        $options = $this->getRouteOptions();
        if (isset($options['controller'])) {
            return $options['controller'];
        }
        return null;
    }

    /**
     * Retrieve a member of the $_COOKIE superglobal
     *
     * If no $key is passed, returns the entire $_COOKIE array.
     *
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getCookie($key = null, $default = null)
    {
        $cookieParams = $this->request->getCookieParams();
        if (array_key_exists($key, $cookieParams)) {
            return $cookieParams[$key];
        }
        return $default;
    }

    public function getModuleKey()
    {
        return 'module';
    }

    public function getModuleName()
    {
        return 'module';
    }

    public function getRoute()
    {
        $routeResult = $this->getRouteResult();
        if (is_null($routeResult)) {
            return false;
        }
        return $routeResult->getMatchedRoute();
    }

    public function getRouteResult()
    {
        return $this->request->getAttribute('Zend\Expressive\Router\RouteResult');
    }

    protected function getRouteOptions()
    {
        if (!$this->routeOptions) {
            $route = $this->getRoute();
            if (!$route) {
                return null;
            }
            $this->routeOptions = $route->getOptions();
        }

        return $this->routeOptions;
    }

    public function isPost()
    {
        $method = $this->request->getMethod();
        if ($method == 'POST') {
            return true;
        }
        return false;
    }

    public function setParam($param, $value)
    {
        $this->request = $this->request->withQueryParams([$param => $value]);
    }
}