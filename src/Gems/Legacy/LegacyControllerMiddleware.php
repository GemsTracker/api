<?php


namespace Gems\Legacy;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Loader\ProjectOverloader;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class LegacyControllerMiddleware implements MiddlewareInterface
{
    public function __construct(ProjectOverloader $loader)
    {
        $this->loader = $loader;
        $this->serviceManager = $this->loader->getServiceManager();
        $this->config = $this->serviceManager->get('config');

    }

    protected function loadControllerDependencies($object)
    {
        $objectProperties = get_object_vars($object);
        foreach($objectProperties as $name=>$value) {
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
        $route = $routeResult->getMatchedRoute();
        $config = $this->loader->getServiceManager()->get('config');
        if ($route) {
            $options = $route->getOptions();
            if (isset($options['controller'])) {

                $controllerName = ucfirst($options['controller']) . 'Controller';

                $controllerClass = null;
                if (!isset($config['controllerDirs'])) {
                    throw new \Exception("No controller dirs set in config");
                }

                foreach($config['controllerDirs'] as $controllerDir) {
                    $controllerClassLocation = $controllerDir.DIRECTORY_SEPARATOR.$controllerName.'.php';
                    if (file_exists($controllerClassLocation)) {
                        include $controllerClassLocation;

                        $legacyRequest = new \Zend_Controller_Request_Http;
                        $legacyResponse = new \Zend_Controller_Response_Http;

                        $controllerObject = $this->loader->create(new $controllerName($legacyRequest, $legacyResponse));
                        $this->loadControllerDependencies($controllerObject);
                    }
                }

                if (!$controllerObject) {
                    throw new \Exception(sprintf(
                        "Controller %s could not be found in paths %s",
                        $controllerName,
                        join('; ', $config['controllerDirs'])
                    ));
                }

                $action = 'indexAction';
                if (isset($options['action'])) {
                    $action = $options['action'] . 'Action';
                }

                if (method_exists($controllerObject, $action) && is_callable([$controllerObject, $action])) {
                    call_user_func_array([$controllerObject, $action], []);
                }

                $view = new \Zend_View;
                $content = $controllerObject->html->render($view);

                $data = [
                    'content' => $content
                ];

                $template = $this->serviceManager->has(TemplateRendererInterface::class)
                    ? $this->serviceManager->get(TemplateRendererInterface::class)
                    : null;

                if ($template) {
                    return new HtmlResponse($template->render('app::gemstracker-responsive', $data));
                }        
                
                return new HtmlResponse($content);

            }


        }

        throw new \Exception('No Controller in route');
    }
}