<?php


namespace Gemstest\Rest\Auth;


use Gems\Rest\Auth\UserEntity;
use League\OAuth2\Server\Entities\UserEntityInterface;
use PHPUnit\Framework\TestCase;

class UserEntityTest extends TestCase
{
    public function testConstruct()
    {
        $user = new UserEntity('testId');
        $this->assertInstanceOf(UserEntityInterface::class, $user);
    }
}