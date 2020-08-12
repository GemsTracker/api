<?php


namespace Gems\Rest\Action;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;

class DashboardConfigController extends ModelRestController
{
    public function createModel()
    {
        $model = new \Gems_Model_JoinModel('chartconfig', 'gems__chart_config', 'gcc');

        $model->setOnLoad('gcc_config', [$this, 'loadJson']);

        $model->setOnSave('gcc_config', [$this, 'saveJson']);
        \Gems_Model::setChangeFieldsByPrefix($model, 'gcc');


        /*$model->set('gcc_tid', 'label', $this->_('Track'));
        $model->set('gcc_rid', 'label', $this->_('Round'));
        $model->set('gcc_sid', 'label', $this->_('Survey'));
        $model->set('gcc_code', 'label', $this->_('Survey code'));
        $model->set('gcc_config', 'label', $this->_('Config'));
        $model->set('gcc_description', 'label', $this->_('Description'));*/

        return $model;
    }

    /**
     * Get one item from the model from an ID field
     *
     * @param $id
     * @param ServerRequestInterface $request
     * @return EmptyResponse|JsonResponse
     */
    public function getOne($id, ServerRequestInterface $request)
    {
        $idField = $this->getIdField();
        if ($idField) {
            $idFilter = $this->getIdFilter($id, $idField);
            $filters = $idFilter + $this->getListFilter($request);

            $row = $this->model->loadFirst($filters);
            $this->logRequest($request, $row);
            if (is_array($row)) {
                $translatedRow = $this->translateRow($row);
                $filteredRow = $this->filterColumns($translatedRow);
                return new JsonResponse($filteredRow);
            }
        }
        return new EmptyResponse(404);
    }

    public function loadJson($json)
    {
        return json_decode($json, true);
    }

    public function saveJson($data)
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
