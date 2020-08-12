<?php


namespace Gems\Rest\Action;


use Gems\Rest\Exception\RestException;
use Gems\Rest\Repository\AccesslogRepository;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Loader\ProjectOverloader;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmptyResponse;
use Mezzio\Helper\UrlHelper;
use Laminas\Diactoros\Response\JsonResponse;

class RelatedTokensController extends ModelRestController
{
    /**
     * @var Adapter
     */
    protected $db;
    /**
     * @var \Gems_Loader
     */
    protected $loader;

    public function __construct(AccesslogRepository $accesslogRepository, ProjectOverloader $loader, UrlHelper $urlHelper, Adapter $db, $LegacyDb)
    {
        $this->db = $db;
        parent::__construct($accesslogRepository, $loader, $urlHelper, $LegacyDb);
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $params = $this->getListFilter($request);
        if (!isset($params['gr2o_patient_nr'])) {
            $exception = new RestException('Patient nr not supplied', 400, 'missing_data');
            $response = new Response();
            return $exception->generateHttpResponse($response);
        }
        if (!isset($params['gr2o_id_organization'])) {
            $exception = new RestException('Organization not supplied', 400, 'missing_data');
            $response = new Response();
            return $exception->generateHttpResponse($response);
        }

        $relation = $request->getAttribute('relation');


        $findByToken = false;
        if (isset($params['tokenId'])) {
            $findByToken = true;
        }

        if (!$findByToken && !isset($params['gto_id_respondent_track'])) {
            $exception = new RestException('Respondent Track ID not supplied', 400, 'missing_data');
            $response = new Response();
            return $exception->generateHttpResponse($response);
        }
        if (!$findByToken && !isset($params['gto_id_survey'])) {
            $exception = new RestException('Survey ID not supplied', 400, 'missing_data');
            $response = new Response();
            return $exception->generateHttpResponse($response);
        }

        list($filters, $order) = $this->getTokenFilters($relation, $params, $findByToken);

        $paginatedFilters = $this->getListPagination($request, $filters);
        $headers = $this->getPaginationHeaders($request, $filters);
        if ($headers === false) {
            return new EmptyResponse(204);
        }

        $rows = $this->model->load($paginatedFilters, $order);

        $translatedRows = [];
        foreach($rows as $key=>$row) {
            $translatedRows[$key] = $this->filterColumns($this->translateRow($row));
        }

        return new JsonResponse($translatedRows, 200, $headers);
    }

    /**
     * Get respondent track id, survey id, track id and survey code from a token ID
     *
     * @param $tokenId
     * @return mixed
     * @throws \Exception
     */
    protected function getInfoFromToken($tokenId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__tokens')
            ->join('gems__surveys', 'gto_id_survey = gsu_id_survey', ['surveyCode' => 'gsu_code'])
            ->columns(['respondentTrackId' => 'gto_id_respondent_track', 'surveyId' => 'gto_id_survey', 'trackId' => 'gto_id_track'])
            ->where(['gto_id_token' => $tokenId])
            ->limit(1);

        $statement = $sql->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();
        if (!$resultSet->current()) {
            throw new \Exception(sprintf('No token found with identifier %s', $tokenId));
        }

        return $resultSet->current();
    }

    /**
     * Get the survey code of a survey
     *
     * @param $surveyId
     * @return mixed
     * @throws \Exception
     */
    protected function getSurveyCodeFromSurveyId($surveyId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__surveys')
            ->columns(['surveyCode' => 'gsu_code'])
            ->where('gsu_id_survey = ?', $surveyId)
            ->limit(1);

        $statement = $sql->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();
        if (!$resultSet->current()) {
            throw new \Exception(sprintf('No survey found with identifier %s', $surveyId));
        }

        $currentRow = $resultSet->current();

        return $currentRow['surveyCode'];
    }

    protected function getTokenFilters($relation, $params, $findByToken)
    {
        $filters = [
            'gr2o_patient_nr' => $params['gr2o_patient_nr'],
            'gr2o_id_organization' => $params['gr2o_id_organization'],
            'grc_success' => 1,
            'gto_completion_time IS NOT NULL',
        ];

        $order = ['gto_round_order'];

        if ($findByToken && $relation !== null) {
            $info = $this->getInfoFromToken($params['tokenId']);
            if (is_array($info)) {
                $params += $info;
            }
        }

        switch($relation) {
            case null:
            case 'current':
            case 'latest':
                if ($findByToken) {
                    $filters['gto_id_token'] = $params['tokenId'];
                } else {
                    $filters['gto_id_respondent_track'] = $params['gto_id_respondent_track'];
                    $filters['gto_id_survey'] = $params['gto_id_survey'];
                    $filter['limit'] = 1;
                    $order = ['gto_round_order DESC'];
                }
                break;

            case 'all':
                $filters['gto_id_survey'] = $params['gto_id_survey'];
                break;

            case 'all-in-track':
                $filters['gto_id_survey'] = $params['gto_id_survey'];
                $filters['gto_id_respondent_track'] = $params['gto_id_respondent_track'];
                break;

            case 'all-in-track-type':
                if (!isset($params['trackId'])) {
                    $params['trackId'] = $this->getTrackIdFromRespondentTrackId($params['gto_id_respondent_track']);
                }
                $filters['gto_id_track'] = $params['TrackId'];
                $filters['gto_id_respondent_track'] = $params['gto_id_respondent_track'];
                break;

            case 'all-code-in-track':
                if (!isset($params['surveyCode'])) {
                    $params['surveyCode'] = $this->getSurveyCodeFromSurveyId($params['gto_id_survey']);
                }
                $filters['gsu_code'] = $params['surveyCode'];
                $filters['gto_id_respondent_track'] = $params['gto_id_respondent_track'];
                break;
        }

        return [$filters, $order];
    }

    /**
     * Get the track ID of a respondentTrack ID
     *
     * @param $respondentTrackId
     * @return mixed
     * @throws \Exception
     */
    protected function getTrackIdFromRespondentTrackId($respondentTrackId)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent2track')
            ->columns(['trackId' => 'gr2t_id_track'])
            ->where('gr2t_id_respondent_track = ?', $respondentTrackId)
            ->limit(1);

        $statement = $sql->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();
        if (!$resultSet->current()) {
            throw new \Exception(sprintf('No respondent track found with identifier %s', $respondentTrackId));
        }

        $currentRow = $resultSet->current();

        return $currentRow['trackId'];
    }
}
