<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\ModelRestController;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class RespondentRestController extends ModelRestController
{
    protected $apiNames = [
        'gr2o_patient_nr'       => 'patient_nr',
        'gr2o_id_organization'  => 'organization_id',
        'gr2o_email'            => 'email',
        'grs_ssn'               => 'ssn',
        'grs_first_name'        => 'first_name',
        'grs_surname_prefix'    => 'surname_prefix',
        'grs_last_name'         => 'last_name',
        'grs_gender'            => 'gender',
        'grs_birthday'          => 'birthday',
        'grs_city'              => 'city',
        'appointments'          => [
            'gr2o_patient_nr' => 'patient_nr',
            'gap_id_organization' => 'organization_id',
            'gap_status' => 'status',
            'gap_admission_time' => 'admission_time',
            'gap_discharge_time' => 'discharge_time',
        ]
    ];

    protected $submodelNames = [
        'appointments' => [
            'name' => 'Model_AppointmentModel',
            'joins' => [
                'gr2o_id_user' => 'gap_id_user',
                'gr2o_id_organization' => 'gap_id_organization',
            ]
        ],
    ];

    public function createModel()
    {
        $model = parent::createModel();

        foreach($this->submodelNames as $name=>$submodelSettings) {
            $submodel = $this->loader->create($submodelSettings['name']);
            $model->addModel($submodel, $submodelSettings['joins'], $name);
        }

        return $model;
    }

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