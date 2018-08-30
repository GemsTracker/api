<?php


namespace Gems\Rest\Auth;


use Gems\Rest\Model\EntityRepositoryAbstract;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository extends EntityRepositoryAbstract implements RefreshTokenRepositoryInterface
{
    protected $entity = 'Rest\\Auth\\RefreshTokenEntity';

    protected $table = 'gems__oauth_refresh_tokens';

    /**
     * Filter data before saving
     *
     * @param $data
     * @return array filtered data
     */
    protected function filterDataForSave($data)
    {
        $data = parent::filterDataForSave($data);

        foreach($data as $key=>$value) {
            if ($value instanceof AccessTokenEntityInterface) {
                $data[$key] = $value->getIdentifier();
            }
        }

        return $data;
    }

    /**
     * Creates a new refresh token
     *
     * @return RefreshTokenEntityInterface
     */
    public function getNewRefreshToken()
    {
        $refreshToken = new RefreshTokenEntity;
        $refreshToken->setRevoked(false);

        return $refreshToken;
    }

    /**
     * Create a new refresh token_name.
     *
     * @param RefreshTokenEntityInterface $refreshTokenEntity
     *
     * @throws UniqueTokenIdentifierConstraintViolationException
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        $result = $this->save($refreshTokenEntity);
        if ($result->getAffectedRows() === 0) {
            throw \Exception('Refresh token not saved');
        }
    }

    /**
     * Revoke the refresh token.
     *
     * @param string $tokenId
     */
    public function revokeRefreshToken($tokenId)
    {
        $newValues = [
            'revoked' => 1,
            'id' => $tokenId
        ];

        $result = $this->save($newValues);
        if ($result->getAffectedRows() === 0) {
            throw \Exception('Refresh token not revoked');
        }
    }

    /**
     * Check if the refresh token has been revoked.
     *
     * @param string $tokenId
     *
     * @return bool Return true if this token has been revoked
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        $filter = [
            'id' => $tokenId
        ];

        if ($refreshToken = $this->loadFirst($filter)) {
            return $refreshToken->getRevoked();
        }

        return false;
    }
}