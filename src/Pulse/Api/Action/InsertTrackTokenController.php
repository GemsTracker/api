<?php


namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Gems\Rest\Exception\RestException;
use Gems\Rest\Security\CheckContentTypeTrait;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Expressive\Helper\UrlHelper;

class InsertTrackTokenController extends RestControllerAbstract
{
    use CheckContentTypeTrait;

    public static $definition = [
        'topic' => 'Insert track token',
        'methods' => [
            'post' => [
                'responses' => [
                    201 => 'Token added to track',
                ],
                'body' => [
                    'gto_id_respondent_track' => 'int',
                    'gto_id_respondent_track' => 'int',
                    'gto_id_survey' => 'int',
                ]
            ],
        ],
    ];

    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @var UrlHelper
     */
    protected $helper;

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct(\Gems_Tracker $tracker, UrlHelper $helper, Adapter $db)
    {
        $this->db = $db;
        $this->helper = $helper;
        $this->tracker = $tracker;
    }

    /**
     * Save a new row to the model
     *
     * Will return status:
     * - 415 when the content type of the data supplied in the request is not allowed
     * - 400 (empty response) if the row is empty or if the model could not save the row AFTER validation
     * - 400 (json response) if the row did not pass validation. Errors will be returned in the body
     * - 201 (empty response) if the row is succesfully added to the model.
     *      If possible a Link header will be supplied to the new record
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return EmptyResponse
     */
    public function post(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($this->checkContentType($request) === false) {
            return new EmptyResponse(415);
        }

        $tokenData = json_decode($request->getBody()->getContents(), true);

        if (!isset($tokenData['gto_id_respondent_track'])) {
            throw new RestException('No respondent track ID supplied', 400, 'missing_data');
        }

        $tokenData['gto_id_round']          = '0';
        $tokenData['gto_round_order']       = $this->getNextRoundOrder($tokenData);


        $respondentTrack = $this->tracker->getRespondentTrack($tokenData['gto_id_respondent_track']);
        $surveyId = $tokenData['gto_id_survey'];

        $userId = 0;
        $token = $respondentTrack->addSurveyToTrack($surveyId, $tokenData, $userId);

        $link = null;

        $routeParams = [
            'id' => $token->getTokenId(),
        ];

        try {
            $location = $this->helper->generate('api.tokens.get', $routeParams);
        } catch(\Zend\Expressive\Router\Exception\InvalidArgumentException $e) {
            $location = null;
        }
        if ($location !== null) {
            return new EmptyResponse(
                201,
                [
                    'Location' => $location,
                ]
            );
        }

        return new EmptyResponse(201);
    }

    protected function getNextRoundOrder($tokenData)
    {
        if (isset($tokenData['gto_round_description'], $tokenData['gto_id_respondent_track'])) {
            $sql = new Sql($this->db);
            $select = $sql->select();
            $select->from('gems__rounds')
                ->columns(['maxRoundOrder' => new Expression('MAX(gro_id_order)')])
                ->join('gems__respondent2track', 'gr2t_id_track = gro_id_track', [])
                ->where('gr2t_id_respondent_track', $tokenData['gto_id_respondent_track'])
                ->where('gro_round_description', $tokenData['gto_round_description']);

            $statement = $sql->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            if ($result->valid() && $result->current()) {
                $row = $result->current();
                $maxValue = reset($row);

                return $maxValue+1;
            }
        }
        return 99999;
    }
}