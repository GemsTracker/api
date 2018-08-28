<?php


namespace Gems\Rest\Repository;


use Zend\Db\Sql\Literal;
use Zend\Db\Sql\Sql;

class LoginAttemptsRepository
{
    /**
     * @var Adapter
     */
    protected $db;

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    public function setLoginAttempt($username, $organizationId, $failed=true)
    {
        $loginAttempt = [
            'gula_login' => $username,
            'gula_id_organization' => $organizationId,
            'gula_failed_logins' => (int)$failed,
        ];

        if ($failed) {
            $loginAttempt['gula_last_failed'] = new Literal('NOW()');
        }
        $sql = new Sql($this->db);
        $insert = $sql->insert('gems__user_login_attempts')
            ->columns(array_keys($loginAttempt))
            ->values($loginAttempt);

        try {
            $statement = $sql->prepareStatementForSqlObject($insert);
            $statement->execute();
        } catch(\Exception $e) {
            throw($e);
        }
    }
}