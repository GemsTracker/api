<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Gems\Rest\Exception\RestException;
use Pulse\Api\Repository\IntakeAnesthesiaCheckRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class CurrentIntakeController extends RestControllerAbstract
{

    /**
     * @var IntakeAnesthesiaCheckRepository
     */
    protected $intakeAnesthesiaCheckRespository;

    public function __construct(IntakeAnesthesiaCheckRepository $intakeAnesthesiaCheckRepository)
    {
        $this->intakeAnesthesiaCheckRespository = $intakeAnesthesiaCheckRepository;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $params = $request->getQueryParams();

        if (!(array_key_exists('gr2o_patient_nr', $params) && array_key_exists('gr2o_id_organization', $params))) {
            throw new RestException('patient number and organization should be supplied ', 1, 'missing_filters', 400);
        }

        $respondentTrackId = null;
        if (array_key_exists('gr2t_id_respondent_track', $params)) {
            $respondentTrackId = $params['gr2t_id_respondent_track'];
        }

        $tokenId = $this->intakeAnesthesiaCheckRespository->getCurrentIntakeToken($params['gr2o_patient_nr'], $params['gr2o_id_organization'], $respondentTrackId);

        if ($tokenId) {
            return new JsonResponse($tokenId);
        }

        return new EmptyResponse();


    }
}