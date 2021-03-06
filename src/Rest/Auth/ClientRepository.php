<?php


namespace Gems\Rest\Auth;

use Gems\Rest\Model\EntityRepositoryAbstract;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\Sql\Sql;
use Zalt\Loader\ProjectOverloader;
use Laminas\Hydrator\NamingStrategy\UnderscoreNamingStrategy;
use Laminas\Hydrator\Reflection;
use Exception;

class ClientRepository extends EntityRepositoryAbstract implements ClientRepositoryInterface
{
    protected $entity = 'Rest\\Auth\\ClientEntity';

    protected $table = 'gems__oauth_clients';

    /**
     * {@inheritdoc}
     */
    public function getClientEntity($clientIdentifier, $grantType, $clientSecret = null, $mustValidateSecret = true)
    {
        $filter = [
            'user_id' => $clientIdentifier,
            'active'  => 1,
        ];
        $client = $this->loadFirst($filter);

        if ($client === false) {
            throw new \Exception('Client with supplied user ID and secret not found');
        }

        // Check if client can use current Grant

        if ($mustValidateSecret) {
            try {
                $this->checkClientSecret($client, $clientSecret);
            } catch (\Exception $e) {
                return;
            }
        }

        return $client;
    }

    /**
     * Verify client secret
     *
     * @param ClientEntityInterface $client
     * @param $clientSecret string secret
     * @return bool has the secret been validated
     */
    public function checkClientSecret(ClientEntityInterface $client, $clientSecret)
    {
        if (!password_verify($clientSecret, $client->getSecret())) {
            throw new \Exception('Client with supplied user ID and secret not found');
        }
    }
}
