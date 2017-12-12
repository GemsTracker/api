<?php


namespace Gems\Rest\Auth;


use Gems\Rest\Model\EntityRepositoryAbstract;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Zend\Db\Sql\Sql;

class UserRepository extends EntityRepositoryAbstract implements UserRepositoryInterface
{
    protected $organizationId;

    protected $username;

    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        list($username, $organizationId) = array_values($this->extractUserInfo($username));

        $container = $this->loader->getServiceManager();
        $legacyLoader = $container->get('LegacyLoader');
        $userLoader = $legacyLoader->getUserLoader();
        $user = $userLoader->getUser($username, $organizationId);

        if (!$user instanceof \Gems_User_User) {
            //throw new \Exception('No user found');
            return;
        }
        // Check password
        $result = $user->authenticate($password);
        if (!$result->isValid()) {
            //throw new \Exception('User could not be authenticated');
            return;
        }

        // Check if this user is allowed to use this grant

        $userEntity = new UserEntity($user->getUserId());

        return $userEntity;
    }

    public function extractUserInfo($username)
    {
        $infoArray = explode('@', $username);

        if (count($infoArray) < 2) {
            throw new \Exception('No organization has been embedded in username');
        }

        $organizationIdentifier = array_pop($infoArray);

        return [
            'username' => join('@', $infoArray),
            'organizationId' => $this->getOrganizationId($organizationIdentifier),
        ];
    }

    public function getOrganizationId($organizationIdentifier)
    {
        $filter = [];
        if (is_numeric($organizationIdentifier)) {
            // Assume identifier is the actual organization ID
            $filter['gor_id_organization'] = $organizationIdentifier;
        } else {
            // Assume identifier is the organization code
            $filter['gor_code'] = $organizationIdentifier;
        }

        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__organizations')
            ->columns(['id' => 'gor_id_organization'])
            ->where($filter)
            ->limit(1);

        $statement = $sql->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();
        if (!$resultSet->current()) {
            throw new \Exception(sprintf('No organization found with identifier %s', $organizationIdentifier));
        }

        $organization = $resultSet->current();

        return $organization['id'];
    }
}