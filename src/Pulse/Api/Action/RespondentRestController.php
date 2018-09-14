<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\ModelRestController;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class RespondentRestController extends ModelRestController
{
    public function post(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $this->model->del('gr2o_id_user', 'validation');
        $this->model->del('gr2o_id_user', 'validations');
        $this->model->del('gr2o_id_user', 'required');

        return parent::post($request, $delegate);
    }

    public function saveRow(ServerRequestInterface $request, $row)
    {
        $row['gr2o_opened_by'] = $this->userId;

        if ($this->method == 'post' && !isset($row['gr2o_id_organization'])) {
            if (isset($row['appointments'])) {
                $firstAppointment = reset($row['appointments']);
                if (isset($firstAppointment['gap_id_organization'])) {
                    $row['gr2o_id_organization'] = $firstAppointment['gap_id_organization'];
                }
            }
        }

        if ($this->method == 'post' && !isset($row['gr2o_id_organization'])) {
            return new JsonResponse(['error' => 'missing_data', 'message' => 'Organization ID missing in sent data'], 400);
        }


        return parent::saveRow($request, $row);
    }
}