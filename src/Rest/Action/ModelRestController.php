<?php

namespace Gems\Rest\Action;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;

class ModelRestController extends ModelRestControllerAbstract
{
    protected $itemsPerPage = 5;

    protected $modelName;

    protected function createModel()
    {
        if ($this->model instanceof \MUtil_Model_ModelAbstract) {
            return $this->model;
        }

        return $this->loader->create($this->modelName);
        //return $this->loader->create('Model_OrganizationModel');
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $routeResult = $request->getAttribute('Zend\Expressive\Router\RouteResult');
        $route = $routeResult->getMatchedRoute();
        if ($route) {
            $options = $route->getOptions();
            if (isset($options['model'])) {
                $this->setModelName($options['model']);
            }
            if (isset($options['itemsPerPage'])) {
                $this->setItemsPerPage($options['itemsPerPage']);
            }
        }

        return parent::process($request, $delegate);
    }

    /**
     * Set the name of the model you want to load
     * @param string|\MUtil_Model_ModelAbstract namespaced classname, project loader classname or actual class of a model
     */
    public function setModelName($modelName)
    {
        if (is_string($modelName)) {
            $this->modelName = $modelName;
        } elseif ($modelName instanceof \MUtil_Model_ModelAbstract) {
            $this->model = $modelName;
        }
    }

    public function setItemsPerPage($itemsPerPage)
    {
        if (is_int($itemsPerPage)) {
            $this->itemsPerPage = $itemsPerPage;
        }
    }
}