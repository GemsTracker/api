<?php


namespace Gems\Rest\Auth;


use Gems\Rest\Model\EntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;

class UserEntity implements UserEntityInterface, EntityInterface
{
    use EntityTrait;

    /**
     * Create a new user instance.
     *
     * @param  string|int  $identifier
     */
    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
    }
}