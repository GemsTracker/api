<?php


namespace Gemstest\Rest\Auth;


use Gems\Rest\Auth\ScopeEntity;
use PHPUnit\Framework\TestCase;

class ScopeEntityTest extends TestCase
{
    public function testGettersAndSetters()
    {
        $scope = new ScopeEntity();

        $scope->setName('testName');
        $this->assertEquals('testName', $scope->getName());

        $scope->setDescription('testDescription');
        $this->assertEquals('testDescription', $scope->getDescription());
    }
}