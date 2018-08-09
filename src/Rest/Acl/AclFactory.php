<?php


namespace Gems\Rest\Acl;


use Interop\Container\ContainerInterface;
use Zend\Permissions\Acl\Acl;
use Zend\ServiceManager\Factory\FactoryInterface;

class AclFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return object Acl
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $repository = $container->get(AclRepository::class);

        return $repository->getAcl();
    }
}