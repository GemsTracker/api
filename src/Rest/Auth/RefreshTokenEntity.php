<?php


namespace Gems\Rest\Auth;


use Gems\Rest\Model\EntityInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;

class RefreshTokenEntity implements RefreshTokenEntityInterface, EntityInterface
{
    protected $id;

    protected $accessTokenId;

    protected $revoked;

    protected $expiresAt;

    /**
     * Get the access token that the refresh token was originally associated with.
     *
     * @return AccessTokenEntityInterface
     */
    public function getAccessToken()
    {
        return $this->accessTokenId;
    }

    /**
     * Get the access token that the refresh token was originally associated with.
     *
     * @return int Access token Id
     */
    public function getAccessTokenId()
    {
        return $this->accessTokenId->getIdentifier();
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
     * Get the token's expiry date time.
     *
     * @return \DateTime
     */
    public function getExpiryDateTime()
    {
        return $this->expiresAt;
    }

    /**
     * Get the token's identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->id;
    }

    /**
     * @return is Refresh token revoked
     */
    public function getRevoked()
    {
        return (bool)$this->revoked;
    }

    /**
     * Set the access token that the refresh token was associated with.
     *
     * @param AccessTokenEntityInterface $accessToken
     */
    public function setAccessToken(AccessTokenEntityInterface $accessToken)
    {
        $this->accessTokenId = $accessToken;
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
     * Set the date time when the token expires.
     *
     * @param \DateTime $dateTime
     */
    public function setExpiryDateTime(\DateTime $dateTime)
    {
        $this->expiresAt = $dateTime;
    }

    /**
     * Set the token's identifier.
     *
     * @param $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->id = $identifier;
    }

    /**
     * set if token is revoked
     *
     * @param $revoked boolean
     */
    public function setRevoked($revoked)
    {

        $this->revoked = (int)$revoked;
    }
}