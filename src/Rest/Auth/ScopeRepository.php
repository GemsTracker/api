<?php


namespace Gems\Rest\Auth;


use Gems\Rest\Model\EntityRepositoryAbstract;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class ScopeRepository extends EntityRepositoryAbstract implements ScopeRepositoryInterface
{
    /**
     * @var string Model Entity
     */
    protected $entity = 'Rest\\Auth\\ScopeEntity';

    /**
     * @var string table name to start the query with
     */
    protected $table = 'gems__oauth_scope';

    /**
     * Return information about a scope.
     *
     * @param string $identifier The scope identifier
     *
     * @return ScopeEntityInterface
     */
    public function getScopeEntityByIdentifier($identifier)
    {
        $filter = [
            'name'      => $identifier,
            'active'    => 1,
        ];
        $scope = $this->loadFirst($filter);

        return $scope;
    }

    /**
     * Given a client, grant type and optional user identifier validate the set of scopes requested are valid and optionally
     * append additional scopes or remove requested scopes.
     *
     * @param ScopeEntityInterface[] $scopes
     * @param string                 $grantType
     * @param ClientEntityInterface  $clientEntity
     * @param null|string            $userIdentifier
     *
     * @return ScopeEntityInterface[]
     */
    public function finalizeScopes(array $scopes, $grantType, ClientEntityInterface $clientEntity, $userIdentifier = null)
    {
        return $scopes;
    }
}