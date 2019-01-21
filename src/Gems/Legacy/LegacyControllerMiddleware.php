<?php

namespace Gems\Legacy;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use MUtil\Controller\Router\ExpressiveRouteWrapper;
use MUtil\Controller\Front;
use MUtil\Controller\Request\ExpressiveRequestWrapper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Loader\ProjectOverloader;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplateRendererInterface;

class LegacyControllerMiddleware implements MiddlewareInterface
{

    /**
     * @var array
     */
    protected $config;

    /**
     * @var ProjectOverloader
     */
    protected $loader;

    /**
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceManager;

    /**
     * @var UrlHelper
     */
    protected $urlHelper;

    /**
     * @var \Zend_View
     */
    protected $view;

    public function __construct(ProjectOverloader $loader, \Zend_View $view, UrlHelper $urlHelper)
    {
        $this->loader         = $loader;
        $this->serviceManager = $this->loader->getServiceManager();
        $this->config         = $this->serviceManager->get('config');
        $this->urlHelper      = $urlHelper;
        $this->view           = $view;
    }

    protected function loadControllerDependencies($object)
    {
        $objectProperties = get_object_vars($object);
        foreach ($objectProperties as $name => $value) {
            if ($value === null) {
                $legacyName = 'Legacy' . ucFirst($name);
                if ($this->serviceManager->has($legacyName)) {
                    $object->$name = $this->serviceManager->get($legacyName);
                }
            }
        }
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $routeResult = $request->getAttribute('Zend\Expressive\Router\RouteResult');

        $route  = $routeResult->getMatchedRoute();
        $config = $this->loader->getServiceManager()->get('config');
        if ($route) {
            $options    = $route->getOptions();
            $controller = $request->getAttribute('controller', 'index');
            $action     = $request->getAttribute('action', 'index');
            $actionName = $action . 'Action';


            $controllerName = ucfirst($controller) . 'Controller';

            $controllerClass = null;
            if (!isset($config['controllerDirs'])) {
                throw new \Exception("No controller dirs set in config");
            }

            foreach ($config['controllerDirs'] as $controllerDir) {
                $controllerClassLocation = $controllerDir . DIRECTORY_SEPARATOR . $controllerName . '.php';
                if (file_exists($controllerClassLocation)) {
                    include $controllerClassLocation;

                    //$legacyRequest = new \Zend_Controller_Request_Http;
                    //$legacyResponse = new \Zend_Controller_Response_Http;
                    $requestWrapper = new ExpressiveRequestWrapper($request);
                    $this->loader->getServiceManager()->setService('LegacyRequest', $requestWrapper);


                    $routeWrapper = new ExpressiveRouteWrapper($request, $this->urlHelper);

                    Front::setRequest($requestWrapper);
                    Front::setRouter($routeWrapper);

                    $resp = new \Zend_Controller_Response_Http();
                    $req  = new \Zend_Controller_Request_Http();
                    $req->setControllerName($controller);
                    $req->setActionName($action);
                    $req->setParams($requestWrapper->getParams()); 
                    
                    Front::setLegacyRequest($req);

                    \Zend_Controller_Front::getInstance()->setControllerDirectory(APPLICATION_PATH . '/controllers');

                    // Defer init, we first need to inject all our dependencies
                    $controllerObject = $this->loader->create($controllerName, $req, $resp, [], false);

                    $this->loadControllerDependencies($controllerObject);
                    $controllerObject->init();
                    //$controllerObject->html = new \MUtil_Html_Sequence();
                    //$controllerObject->initHtml();
                    break;
                }
            }

            if (!$controllerObject) {
                throw new \Exception(sprintf(
                                "Controller %s could not be found in paths %s",
                                $controllerName,
                                join('; ', $config['controllerDirs'])
                ));
            }

            if (method_exists($controllerObject, $actionName) && is_callable([$controllerObject, $actionName])) {
                $response = call_user_func_array([$controllerObject, $actionName], []);
                if ($response instanceof ResponseInterface) {
                    return $response;
                }
            } else {
                throw new \Exception(sprintf(
                                "Controller action %s could not be found in paths %s",
                                $actionName
                ));
            }

            $content = $controllerObject->html->render($this->view);
            
            $data = [
                'content' => $content,
            ];
            
            // TODO naar layout rendering middleware zetten zodat deze niet te groot wordt
            /** @var \Gems_Menu $menu */
            $menu = $this->serviceManager->get('LegacyMenu');
            if ($menu->isVisible()) {

                // Make sure the actual $request and $controller in use at the end
                // of the dispatchloop is used and make \Zend_Navigation object
                $data['menuHtml'] = $menu->render($this->view);
            }

            $template = $this->serviceManager->has(TemplateRendererInterface::class) ? $this->serviceManager->get(TemplateRendererInterface::class) : null;

            if ($template) {
                return new HtmlResponse($template->render('app::gemstracker-responsive', $data));
            }

            return new HtmlResponse($content);
        }

        throw new \Exception('No Controller in route');
    }

}
