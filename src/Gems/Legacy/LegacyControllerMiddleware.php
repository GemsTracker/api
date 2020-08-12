<?php

namespace Gems\Legacy;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use MUtil\Controller\Response\ExpressiveResponseWrapper;
use MUtil\Controller\Router\ExpressiveRouteWrapper;
use MUtil\Controller\Front;
use MUtil\Controller\Request\ExpressiveRequestWrapper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Loader\ProjectOverloader;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;

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
     * @var \Gems_Menu
     */
    protected $menu;

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

    public function __construct(ProjectOverloader $loader, \Zend_View $view, UrlHelper $urlHelper, TemplateRendererInterface $template, $config, $LegacyMenu)
    {
        $this->config         = $config;
        $this->loader         = $loader;
        $this->menu           = $LegacyMenu;
        $this->serviceManager = $this->loader->getServiceManager();
        $this->template       = $template;
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
        $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');

        $route  = $routeResult->getMatchedRoute();
        if ($route) {
            $options    = $route->getOptions();
            $controller = $request->getAttribute('controller', 'index');
            $action     = $request->getAttribute('action', 'index');
            $actionName = $action . 'Action';


            $controllerName = ucfirst(str_replace('-', '', ucwords($controller, '-'))) . 'Controller';

            $controllerClass = null;
            if (!isset($this->config['controllerDirs'])) {
                throw new \Exception("No controller dirs set in config");
            }
            \Zend_Controller_Action_HelperBroker::addPrefix('Gems_Controller_Action_Helper');

            foreach ($this->config['controllerDirs'] as $controllerDir) {
                $controllerClassLocation = $controllerDir . DIRECTORY_SEPARATOR . $controllerName . '.php';
                if (file_exists($controllerClassLocation)) {
                    include $controllerClassLocation;

                    //$legacyRequest = new \Zend_Controller_Request_Http;
                    //$legacyResponse = new \Zend_Controller_Response_Http;
                    $requestWrapper = new ExpressiveRequestWrapper($request);
                    $this->serviceManager->setService('LegacyRequest', $requestWrapper);

                    $response = new ExpressiveResponseWrapper(new HtmlResponse(''));



                    $routeWrapper = new ExpressiveRouteWrapper($request, $this->urlHelper);

                    Front::setRequest($requestWrapper);
                    Front::setResponse($response);
                    Front::setRouter($routeWrapper);

                    $resp = new \Zend_Controller_Response_Http();
                    $req  = new \Zend_Controller_Request_Http();
                    $req->setControllerName($controller);
                    $req->setActionName($action);
                    $req->setParams($requestWrapper->getParams());

                    Front::setLegacyRequest($req);

                    $bootstrap = \MUtil_Bootstrap::bootstrap(array('fontawesome' => true));
                    \MUtil_Bootstrap::enableView($this->view);

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
                                join('; ', $this->config['controllerDirs'])
                ));
            }

            if (method_exists($controllerObject, $actionName) && is_callable([$controllerObject, $actionName])) {

                $menuItem = $this->menu->find(['action' => $action, 'controller' => $controller]);
                if ($menuItem instanceof \Gems_Menu_SubMenuItem) {
                    $this->menu->setCurrent($menuItem);
                }

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
            if ($this->menu->isVisible()) {

                // Make sure the actual $request and $controller in use at the end
                // of the dispatchloop is used and make \Zend_Navigation object
                $data['menuHtml'] = $this->menu->render($this->view);
            }

            $response = Front::getResponse()->getResponse();
            $headers = $response->getHeaders();
            $statusCode = $response->getStatusCode();

            if ($this->template) {
                return new HtmlResponse($this->template->render('app::gemstracker-responsive', $data), $statusCode, $headers);
            }

            return new HtmlResponse($content, $statusCode, $headers);
        }

        throw new \Exception('No Controller in route');
    }

}
