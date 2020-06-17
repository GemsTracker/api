<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\ModelRestController;
use Gems\Rest\Repository\AccesslogRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Zend\Expressive\Helper\UrlHelper;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zalt\Loader\ProjectOverloader;

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

    /**
     * @var \Gems_User_User
     */
    protected $currentUser;

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
            'intake orth',
        ],
        'gor_code' => [
            'xpert',
            'helder',
            'velthuis',
            'eva',
            'xpert-ortho',
        ]
    ];

    protected $orgSpecificFilter = [
        'heuvelrug' => [
            'gsu_code' => [
                'anesthesie',
                'intake OHK',
            ],
            'gor_code' => [
                'heuvelrug',
            ],
        ],
    ];

    public function __construct(AccesslogRepository $accesslogRepository, ProjectOverloader $loader, UrlHelper $urlHelper, $LegacyDb, $LegacyCurrentUser)
    {
        $this->currentUser = $LegacyCurrentUser;
        parent::__construct($accesslogRepository, $loader, $urlHelper, $LegacyDb);
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $this->getId($request);

        if ($id !== null) {
            $idField = $this->getIdField();
            if ($idField) {
                $baseOrganizationCode = $this->currentUser->getBaseOrganization()->getCode();
                $defaultFilter = $this->defaultFilter;
                if (isset($this->orgSpecificFilter[$baseOrganizationCode])) {
                    $defaultFilter = $this->orgSpecificFilter[$baseOrganizationCode];
                }

                $filter = $this->getIdFilter($id, $idField);
                $filter = array_merge($filter, $defaultFilter);

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
            return new EmptyResponse(200);
        }

        return new JsonResponse(['error' => 'missing_data', 'message' => 'patient nr required'],400);
    }
}
