<?php


namespace Gems\Rest\Auth;


use Gems\Rest\Model\EntityRepositoryAbstract;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository extends EntityRepositoryAbstract implements AuthCodeRepositoryInterface
{
    protected $entity = 'Rest\\Auth\\AuthCodeEntity';

    protected $table = 'gems__oauth_auth_codes';

    //protected $nonPersistColumns = ['redirect'];

    protected function filterDataForSave($data)
    {
        $data = parent::filterDataForSave($data);

        if (isset($data['scopes'])) {
            $scopeNames = [];
            foreach($data['scopes'] as $scope) {
                if ($scope instanceof ScopeEntityInterface) {
                    $scopeNames[] = $scope->getIdentifier();
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
     * Creates a new AuthCode
     *
     * @return AuthCodeEntityInterface
     */
    public function getNewAuthCode()
    {
        $authCode = new AuthCodeEntity();
        $authCode->setRevoked(0);

        return $authCode;
    }

    /**
     * Persists a new auth code to permanent storage.
     *
     * @param AuthCodeEntityInterface $authCodeEntity
     *
     * @throws UniqueTokenIdentifierConstraintViolationException
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        $result = $this->save($authCodeEntity);
        if ($result->getAffectedRows() === 0) {
            throw new \Exception('Auth code not saved');
        }
    }

    /**
     * Revoke an auth code.
     *
     * @param string $codeId
     */
    public function revokeAuthCode($codeId)
    {
        $newValues = [
            'id' => $codeId,
            'revoked' => 1
        ];

        $result = $this->save($newValues);

        if ($result->getAffectedRows() === 0) {
            throw new \Exception('Auth code not revoked');
        }

        /*if ($authCode = $this->loadFirst($filter)) {
            $authCode->revoked = 1;
            $this->save($authCode);
        } else {
            return false;
        }*/
    }

    /**
     * Check if the auth code has been revoked.
     *
     * @param string $codeId
     *
     * @return bool Return true if this code has been revoked
     */
    public function isAuthCodeRevoked($codeId)
    {
        $filter = [
            'id' => $codeId
        ];

        if ($accessToken = $this->loadFirst($filter)) {
            return $accessToken->getRevoked();
        }

        return false;
    }
}