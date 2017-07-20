<?php


namespace App\Action;

use Zend\ServiceManager\Factory\FactoryInterface;

class ModelFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new $requestedName($container);
    }
}