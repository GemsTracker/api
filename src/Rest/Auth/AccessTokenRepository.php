<?php


namespace Gems\Rest\Auth;


use Gems\Rest\Model\EntityRepositoryAbstract;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Laminas\Db\Sql\Where;

class AccessTokenRepository extends EntityRepositoryAbstract implements AccessTokenRepositoryInterface
{
    protected $entity = 'Rest\\Auth\\AccessTokenEntity';

    protected $table = 'gems__oauth_access_tokens';

    protected function filterDataForSave($data)
    {
        $data = parent::filterDataForSave($data);

        if (isset($data['scopes']) && is_array($data['scopes'])) {
            $scopeNames = [];
            foreach ($data['scopes'] as $scope) {
                if ($scope instanceof ScopeEntity) {
                    $scopeNames[] = $scope->getIdentifier();
                } elseif(is_string($scope)) {
                    $scopeNames[] = $scope;
                }
            }
            $data['scopes'] = join(',', $scopeNames);
        }

        if (isset($data['client_id']) && $data['client_id'] instanceof ClientEntityInterface) {
            $data['client_id'] = $data['client_id']->getIdentifier();
        }

        return $data;
    }

    /**
     * Find a valid access token
     *
     * @param UserEntityInterface $user
     * @param ClientEntityInterface $client
     * @return AccessTokenEntityInterface|false
     */
    public function findValidToken(UserEntityInterface $user, ClientEntityInterface $client)
    {
        $now = new \DateTime;

        $filter = new Where();
        $filter->equalTo('user_id', $user->getIdentifier())
            ->equalTo('client_id', $client->getIdentifier())
            ->equalTo('revoked', 0)
            ->greaterThan('expires_at', $now->format('Y-m-d H:i:s'));

        $token = $this->loadFirst($filter);

        return $token;
    }

    /**
     * Create a new access token
     *
     * @param ClientEntityInterface  $clientEntity
     * @param ScopeEntityInterface[] $scopes
     * @param mixed                  $userIdentifier
     *
     * @return AccessTokenEntityInterface
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        $accessToken = new AccessTokenEntity($userIdentifier, $scopes);
        $accessToken->setRevoked(0);
        $accessToken->setClient($clientEntity);

        return $accessToken;
    }

    /**
     * Persists a new access token to permanent storage.
     *
     * @param AccessTokenEntityInterface $accessTokenEntity
     *
     * @throws UniqueTokenIdentifierConstraintViolationException
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        $result = $this->save($accessTokenEntity);
        if ($result->getAffectedRows() === 0) {
            throw new \Exception('Access token not saved');
        }
    }

    /**
     * Revoke an access token.
     *
     * @param string $tokenId
     */
    public function revokeAccessToken($tokenId)
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setRevoked(true);

        $filter = [
            'id' => $tokenId
        ];

        $result = $this->save($accessToken, $filter, true);
        if ($result->getAffectedRows() === 0) {
            throw new \Exception('Access token not revoked');
        }

        /*if ($accessToken = $this->loadFirst($filter)) {
            $accessToken->setRevoked(true);
            $result = $this->save($accessToken);
            if ($result->getAffectedRows() === 0) {
                throw new \Exception('Access token not revoked');
            }
        } else {
            return false;
        }*/
    }

    /**
     * Check if the access token has been revoked.
     *
     * @param string $tokenId
     *
     * @return bool Return true if this token has been revoked
     */
    public function isAccessTokenRevoked($tokenId)
    {
        $filter = [
            'id' => $tokenId
        ];

        if ($accessToken = $this->loadFirst($filter)) {
            return $accessToken->getRevoked();
        }

        return false;
    }
}
