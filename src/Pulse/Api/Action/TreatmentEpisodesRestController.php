<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Gems\Rest\Repository\RespondentRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\TreatmentEpisodesRepository;
use Zend\Diactoros\Response\JsonResponse;

class TreatmentEpisodesRestController extends RestControllerAbstract
{
    public static $definition = [
        'topic' => 'Treatment episodes',
        'methods' => [
            'get' => [
                'params' => [
                    'id' => [
                        'type' => 'int',
                        'required' => true,
                    ]
                ],
                'responses' => [
                    200 => [
                        'gte_id_episode' => 'int',
                        'tracks' => 'array',
                        '~treatment' => 'object',
                        '~treatmentAppointment' => 'datetime',
                        '~side' => 'string',
                    ],
                    400 => 'treatment episode id missing',
                ],
            ],
        ],
    ];

    /**
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * @var RespondentRepository
     */
    protected $respondentRepository;

    /**
     * @var TreatmentEpisodesRepository
     */
    protected $treatmentEpisodesRepository;

    /**
     * TreatmentEpisodesRestController constructor.
     * @param TreatmentEpisodesRepository $treatmentEpisodesRepository
     * @param $currentUser
     */
    public function __construct(TreatmentEpisodesRepository $treatmentEpisodesRepository, RespondentRepository $respondentRepository, $LegacyCurrentUser)
    {
        $this->treatmentEpisodesRepository = $treatmentEpisodesRepository;
        $this->respondentRepository = $respondentRepository;
        $this->currentUser = $LegacyCurrentUser;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $request->getAttribute('id');
        if ($id === null) {
            throw new RestException('Treatment episode needs an ID in the id parameter', 1, 'treatment_episode_id_missing', 400);
        }


        $filters = $request->getQueryParams();

        if ($id == 0 && !(array_key_exists('gr2o_patient_nr', $filters) && array_key_exists('gr2o_id_organization', $filters))) {
            throw new RestException('patient number and organization should be supplied when episode ID is 0 ', 1, 'missing_filters', 400);
        }

        $queryParams = $request->getQueryParams();

        if (array_key_exists('all_organizations', $queryParams) && $queryParams['all_organizations'] == 1) {
            $respondentId = $this->respondentRepository->getRespondentId($filters['gr2o_patient_nr'], $filters['gr2o_id_organization']);
            $filters['gr2o_id_user'] = $respondentId;
            $filters['gr2o_id_organization'] = array_keys($this->currentUser->getAllowedOrganizations());
            unset($filters['gr2o_patient_nr']);
        }

        $treatmentEpisode = $this->treatmentEpisodesRepository->getTreatmentEpisode($id, $filters);

        return new JsonResponse($treatmentEpisode);
    }
}