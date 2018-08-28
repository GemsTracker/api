<?php


namespace Rest\Repository;


use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Literal;
use Zend\Db\Sql\Sql;

class AccesslogRepository
{
    /**
     * @var Adapter
     */
    protected $db;

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
    protected function checkAction($action, $method)
    {
        $check = 'gls_on_action';
        switch($method) {
            case 'POST':
            case 'PATCH':
                $check = 'gls_on_action';
                break;
            case 'DELETE':
                $check = 'gls_on_post';
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
            'gls_changed' => new Literal('NOW'),
            'gls_changed_by' => 0,
            'gls_created' => new Literal('NOW'),
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
            }

            $this->actions = $actions;
        }

        return $this->actions;
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
            $output[$row['gls_name']] = $row;
            $result->next();
        }

        return $output;
    }

    /**
     * Log the action
     *
     * @param $action string action
     * @param $method string method
     * @param $changeAction bool action changes something
     * @param $message array|string message
     * @param $data array|string request data
     * @param $ip string Remote IP
     * @param null $by User ID
     * @param null $respondentId Respondent ID
     * @return bool
     */
    public function logAction($action, $method, $changeAction, $message, $data, $ip, $by=null, $respondentId=null)
    {
        $dbAction = $this->getAction($action);

        if ($this->checkAction($dbAction, $method) === false) {
            return null;
        }

        $log = [
            'gla_action' => $action,
            'gla_method' => $method,
            'gla_by' => $by,
            'gla_changed' => (int)$changeAction,
            'gla_message' => json_encode($message),
            'gla_data' => json_encode($data),
            'gla_remote_ip' => $ip,
            'gla_respondent_id' => $respondentId,
        ];

        $sql = new Sql($this->db);
        $insert = $sql->insert();
        $insert->into('gems__log_activity')
            ->columns(array_keys($log))
            ->values($log);

        try {
            $statement = $sql->prepareStatementForSqlObject($insert);
            $statement->execute();
            return true;
        } catch(\Exception $e) {
            return false;
        }
    }
}