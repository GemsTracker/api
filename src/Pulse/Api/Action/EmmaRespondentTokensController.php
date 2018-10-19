<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\ModelRestController;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class EmmaRespondentTokensController extends ModelRestController
{
    protected $apiNames = [
        'gr2o_patient_nr' => 'patient_nr',
        'gor_name' => 'organization',
        'gto_id_token' => 'token',
        'gto_id_survey' => 'survey_id',
        'gsu_survey_name' => 'survey_name',
        'gtr_track_name' => 'track_name',
        'gto_round_description' => 'round_description',
        'gto_completion_time' => 'completion_time',
        'gto_reception_code' => 'reception_code',
        'gto_valid_from' => 'valid_from',
        'gto_valid_until' => 'valid_until',
        'grc_success' => 'status_ok',
    ];

    protected $defaultFilter = [
        'gsu_code' => [
            'anesthesie',
            'intake XHC',
            'intake PC',
            'intake D',
            'intake HV',
            'intake PC',
            'intake FL',
            'intake PR',
            'ZKN',
        ]
    ];

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $this->getId($request);

        if ($id !== null) {
            $idField = $this->getIdField();
            if ($idField) {
                $filter = $this->getIdFilter($id, $idField);
                $filter = array_merge($filter, $this->defaultFilter);

                $rows = $this->model->load($filter);
                if (is_array($rows)) {
                    $translatedRows = [];
                    foreach($rows as $row) {
                        $row = $this->translateRow($row);
                        $row = $this->filterColumns($row);
                        $translatedRows[] = $row;
                    }
                    return new JsonResponse($translatedRows);
                }
            }
            return new EmptyResponse(404);
        }

        return new JsonResponse(['error' => 'missing_data', 'message' => 'patient nr required'],400);
    }
}