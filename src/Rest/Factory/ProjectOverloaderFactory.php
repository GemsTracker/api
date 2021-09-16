<?php


namespace Gems\Rest\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Zalt\Loader\ProjectOverloader;

class ProjectOverloaderFactory implements FactoryInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ProjectOverloader
     */
    protected $loader;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $this->container = $container;
        $this->loader = $container->get('loader');

        //$requestedName = $this->stripOverloader($requestedName);
        //echo $requestedName;
        return $this->loader->create($requestedName);
    }

    protected function stripOverloader($requestedName)
    {
        $overloaders = $this->loader->getOverloaders();
        foreach($overloaders as $overloader) {
            if (strpos($requestedName, $overloader) === 0 || strpos($requestedName, '\\'.$overloader) === 0) {
                $requestedName = str_replace([$overloader.'_', $overloader], '', $requestedName);
                return $requestedName;
            }
        }

        return $requestedName;
    }
}
