<?php


namespace Gems\Rest\Log;


use Interop\Container\ContainerInterface;
use Laminas\Log\LoggerAbstractServiceFactory;
use Laminas\Log\PsrLoggerAdapter;

class PsrLoggerAbstractServiceFactory extends LoggerAbstractServiceFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $logger = parent::__invoke($container, $requestedName, $options);
        return new PsrLoggerAdapter($logger);
    }
}
