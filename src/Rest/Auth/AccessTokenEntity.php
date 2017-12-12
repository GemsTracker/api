<?php


namespace Gems\Rest\Auth;

use Gems\Rest\Model\EntityInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;

class AccessTokenEntity implements AccessTokenEntityInterface, EntityInterface
{
    use AccessTokenTrait;

    /**
     * @var League\OAuth2\Server\Entities\ClientEntityInterface;
     */
    protected $clientId;

    /**
     * @var \DateTime when the token expires
     */
    protected $expiresAt;

    /**
     * @var bool Check if token is revoked
     */
    protected $revoked;

    /*
     * @var string
     */
    protected $id;

    /**
     * @var array list of ScopeEntities
     */
    protected $scopes;

    /**
     * @var int
     */
    protected $userId;

    public function __construct($userIdentifier = null, array $scopes = [])
    {
        $this->setUserIdentifier($userIdentifier);

        foreach($scopes as $scope) {
            $this->addScope($scope);
        }
    }

    /**
     * Associate a scope with the token.
     *
     * @param ScopeEntityInterface $scope
     */
    public function addScope(ScopeEntityInterface $scope)
    {
        $this->scopes[$scope->getIdentifier()] = $scope;
    }

    /**
     * Get the client that the token was issued to.
     *
     * @return ClientEntityInterface
     */
    public function getClient()
    {
        return $this->clientId;
    }

    /**
     * Get the client that the token was issued to.
     *
     * @return ClientEntityInterface
     */
    public function getClientId()
    {
        return $this->clientId->getIdentifier();
    }

    /**
     * Get the token's expiry date time.
     *
     * @return \DateTime
     */
    public function getExpiryDateTime()
    {
        return $this->expiresAt;
    }

    /**
     * Get the token's expiry date time.
     *
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->id;
    }

    public function getRevoked()
    {
        return (bool)$this->revoked;
    }

    /**
     * Return an array of scopes associated with the token.
     *
     * @return ScopeEntityInterface[]
     */
    public function getScopes()
    {
        if (is_array($this->scopes)) {
            return array_values($this->scopes);
        }

        return null;
    }

    /**
     * Get the token user's identifier.
     *
     * @return string|int
     */
    public function getUserIdentifier()
    {
        return $this->userId;
    }

    /**
     * Set the client that the token was issued to.
     *
     * @param ClientEntityInterface $client
     */
    public function setClient(ClientEntityInterface $client)
    {
        $this->clientId = $client;
    }

    /**
     * Set the date time when the token expires.
     *
     * @param \DateTime $dateTime
     */
    public function setExpiryDateTime(\DateTime $dateTime)
    {
        $this->expiresAt = $dateTime;
    }

    /**
     * Set the date time when the token expires.
     *
     * @param \DateTime $dateTime
     */
    public function setExpiresAt(\DateTime $dateTime)
    {
        $this->expiresAt = $dateTime;
    }

    /**
     * @param mixed $identifier
     */
    public function setId($identifier)
    {
        $this->id = $identifier;
    }

    /**
     * @param mixed $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->id = $identifier;
    }

    public function setRevoked($revoked)
    {
        $this->revoked = (bool)$revoked;
    }

    /**
     * Set the identifier of the user associated with the token.
     *
     * @param string|int $identifier The identifier of the user
     */
    public function setUserIdentifier($identifier)
    {
        $this->userId = $identifier;
    }
}