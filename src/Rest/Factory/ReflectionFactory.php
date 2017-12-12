<?php


namespace Gems\Rest\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Exception;
use ReflectionClass;
use ReflectionParameter;

class ReflectionFactory implements FactoryInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $this->container = $container;
        $loader = $container->get('loader');

        $className = $loader->find($requestedName);

        $reflector = new ReflectionClass($className);

        if (! $reflector->isInstantiable()) {
            throw new Exception("Target class $className is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $className;
        }

        $dependencies = $constructor->getParameters();

        $results = [];

        foreach($dependencies as $dependency) {
            if ($class = $dependency->getClass() !== null) {
                $results[] = $this->resolveClass($dependency);
            } else {
                $results[] = $this->resolveDefault($dependency);
            }
        }

        return $loader->create($className, ...$results);
    }

    /**
     * If a construct parameter have a class declaration, try getting the class from the service loader
     *
     * @param ReflectionParameter $parameter
     * @return object class instance
     * @throws Exception Dependency can't be resolved
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        $className = $parameter->getClass()->getName();
        if ($this->container->has($className)) {
            return $this->container->get($className);
        }

        return $this->resolveDefault($parameter);
    }

    /**
     * If a construct parameters do not have a class declaration, see if it has a default value,
     * otherwise it can't be loaded
     *
     * @param ReflectionParameter $parameter
     * @return mixed Default value
     * @throws Exception Dependency can't be resolved
     */
    protected function resolveDefault(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new Exception("Dependency [$parameter->name] can't be resolved in class {$parameter->getDeclaringClass()->getName()}");
    }
}