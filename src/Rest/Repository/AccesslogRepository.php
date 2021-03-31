<?php


namespace Gems\Rest\Repository;


use Psr\Http\Message\ServerRequestInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;

class AccesslogRepository
{

    protected $actions = [];
    /**
     * @var Adapter
     */
    protected $db;

    public $requestAttributeName = 'access_log_entry';

    public function __construct(Adapter $db)
    {
        $this->db = $db;
        $this->actions = $this->getActions();
    }

    /**
     * Check if the action should be logged
     *
     * @param $action array
     * @param $method string
     * @return bool
     */
    protected function checkAction($action, $method, $changed=false)
    {
        $check = 'gls_on_action';
        switch($method) {
            case 'GET':
                $check = 'gls_on_action';
                break;

            case 'POST':
            case 'PATCH':
            case 'DELETE':

                $check = 'gls_on_post';
                if ($changed) {
                    $check = 'gls_on_change';
                }
                break;
            case 'OPTIONS':
                return false;
                break;
        }

        if ($action[$check] != 1) {
            return false;
        }
        return true;
    }

    /**
     * Get a specific action from the database actions
     *
     * @param $action
     * @return mixed
     */
    protected function getAction($action)
    {
        if (isset($this->actions[$action])) {
            return $this->actions[$action];
        }

        $logAction = [
            'gls_name' => $action,
            'gls_when_no_user' => 0,
            'gls_on_action' => 0,
            'gls_on_post' => 0,
            'gls_on_change' => 0,
            'gls_changed' => new Expression('NOW()'),
            'gls_changed_by' => 0,
            'gls_created' => new Expression('NOW()'),
            'gls_created_by' => 0,
        ];

        $sql = new Sql($this->db);
        $insert = $sql->insert();
        $insert->into('gems__log_setup')
            ->columns(array_keys($logAction))
            ->values($logAction);

        try {
            $statement = $sql->prepareStatementForSqlObject($insert);
            $statement->execute();


        } catch(\Exception $e) {
            return false;
        }

        $actions = $this->getDbActions(false);
        return $actions[$action];
    }

    /**
     * Get a list of all the actions
     *
     * @return array
     */
    protected function getActions($cache=true)
    {
        if (!$this->actions) {
            $actions = null;
            if ($cache) {
                $actions = $this->getCacheActions();
            }

            if (!$actions) {
                $actions = $this->getDbActions();
                $this->setCacheActions($actions);
            }

            $this->actions = $actions;
        }

        return $this->actions;
    }

    /**
     * Get the current Action from the route
     *
     * @param ServerRequestInterface $request
     * @return mixed|string
     */
    public function getApiAction(ServerRequestInterface $request)
    {
        $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');
        $route = $routeResult->getMatchedRoute();
        $action = str_replace(['.get', '.fixed'], '', $route->getName());
        if (strpos($action, 'api.') !== 0) {
            $action = 'api.'.$action;
        }

        return $action;
    }

    /**
     * Get database actions from the cache
     *
     * @return null
     */
    protected function getCacheActions()
    {
        return null;
    }

    /**
     * Get the data of the request
     *
     * @param $method string request method
     * @param ServerRequestInterface $request
     * @return array
     */
    public function getData(ServerRequestInterface $request)
    {
        $method = $request->getMethod();
        switch($method) {
            case 'GET':
            case 'DELETE':
                $data = $request->getQueryParams();
                break;
            case 'PATCH':
            case 'POST':
                $data = $request->getBody()->getContents();
                break;
        }

        return $data;
    }

    /**
     * Get all actions from database
     *
     * @return array
     */
    protected function getDbActions()
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__log_setup')
            ->order('gls_name');

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        $output = [];
        while($result->valid()) {
            $row = $result->current();
            if ($row) {
                $output[$row['gls_name']] = $row;
            }
            $result->next();

        }

        return $output;
    }

    /**
     * Get the user IP address
     *
     * @param ServerRequestInterface $request
     * @return string|null
     */
    public function getIp(ServerRequestInterface $request)
    {
        $params = $request->getServerParams();
        if (isset($params['REMOTE_ADDR'])) {
            return $params['REMOTE_ADDR'];
        }
        return null;
    }

    /**
     * Get the message from a request
     *
     * @param ServerRequestInterface $request
     * @return null
     */
    public function getMessage(ServerRequestInterface $request)
    {
        return null;
    }

    /**
     * Log the action
     *
     * @param ServerRequestInterface $request
     * @param null $respondentId
     * @return bool|null
     */
    public function logAction(ServerRequestInterface $request, $respondentId=null)
    {
        return $this->logRequest($request, $respondentId);
    }

    /**
     * Log when a request has changed something in the resource
     *
     * @param ServerRequestInterface $request
     * @param null $respondentId
     * @return bool|null
     */
    public function logChange(ServerRequestInterface $request, $respondentId=null)
    {
        return $this->logRequest($request, $respondentId, true);
    }

    /**
     * Log a request
     *
     * @param ServerRequestInterface $request
     * @param bool $respondentId Respondent ID belonging to the request
     * @param bool $changed have there been changes to the resource
     * @return bool|null
     */
    protected function logRequest(ServerRequestInterface $request, $respondentId=null, $changed=false)
    {
        $action = $this->getApiAction($request);
        $dbAction = $this->getAction($action);
        $method = $request->getMethod();
        if ($this->checkAction($dbAction, $method, $changed) === false) {
            return null;
        }

        $update = true;

        $log = $request->getAttribute($this->requestAttributeName);
        if ($log === null || (int)$changed != $log['gla_changed']) {
            $update = false;
            $by = $request->getAttribute('user_id');
            $organization = $request->getAttribute('user_organization');
            $role = $request->getAttribute('user_role', '');
            $message = $this->getMessage($request);
            $data = $this->getData($request);
            $ip = $this->getIp($request);

            $log = [
                'gla_action' => $dbAction['gls_id_action'],
                'gla_method' => $method,
                'gla_by' => $by,
                'gla_changed' => (int)$changed,
                'gla_message' => json_encode($message),
                'gla_data' => json_encode($data),
                'gla_remote_ip' => $ip,
                'gla_respondent_id' => $respondentId,
                'gla_organization' => $organization,
                'gla_role' => $role,
            ];
        } elseif ($respondentId && $log['gla_respondent_id'] === null) {
            $log['gla_respondent_id'] = $respondentId;
        } else {
            return null;
        }

        $sql = new Sql($this->db);
        if ($update) {
            $update = $sql->update();
            $update->table('gems__log_activity')
                ->set($log)
                ->where(['gla_id' => $log['gla_id']]);
            try {
                $statement = $sql->prepareStatementForSqlObject($update);
                $statement->execute();
                return $log;
            } catch (\Exception $e) {
                return false;
            }
        } else {
            $insert = $sql->insert();
            $insert->into('gems__log_activity')
                ->columns(array_keys($log))
                ->values($log);

            try {
                $statement = $sql->prepareStatementForSqlObject($insert);
                $result = $statement->execute();
                if ($id = $result->getGeneratedValue()) {
                    $log['gla_id'] = $id;
                }
                return $log;
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    protected function setCacheActions($actions)
    {
        return null;
    }
}
