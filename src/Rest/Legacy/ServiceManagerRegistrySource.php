<?php


namespace Gems\Rest\Legacy;


use Zend\ServiceManager\ServiceManager;

class ServiceManagerRegistrySource implements \MUtil_Registry_SourceInterface
{
    /**
     * @var Array list of source containers
     */
    protected $containers;

    public function __construct(ServiceManager $container)
    {
        $this->containers[] = $container;
    }

    /**
     * Adds an extra source container to this object.
     *
     * @param mixed $container \Zend_Config, array or \ArrayObject
     * @param string $name An optional name to identify the container
     * @return \MUtil_Registry_Source
     */
    public function addRegistryContainer($container, $name = null)
    {
        if ($container instanceof \Zend_Config) {
            $container = $container->toArray();
        }
        if (is_array($container)) {
            $container = new \ArrayObject($container);
        }
        if ($container instanceof \ArrayObject) {
            $container->setFlags(\ArrayObject::ARRAY_AS_PROPS);
        }

        // Always append in reverse order
        if (null === $name) {
            array_unshift($this->containers, $container);
        } else {
            $this->containers = array($name => $container) + $this->containers;
        }

        return $this;
    }

    /**
     * Apply this source to the target.
     *
     * @param \MUtil_Registry_TargetInterface $target
     * @return boolean True if $target is OK with loaded requests
     */
    public function applySource(\MUtil_Registry_TargetInterface $target)
    {
        foreach ($target->getRegistryRequests() as $name) {
            if (! $this->applySourceContainers($target, $name)) {
                // Log that source could not be found
            }
        }

        $result = $target->checkRegistryRequestsAnswers();

        $target->afterRegistry();

        return $result;
    }

    /**
     *
     * @param \MUtil_Registry_TargetInterface $target
     * @param string $name
     * @return boolean A correct match was found
     */
    protected function applySourceContainers(\MUtil_Registry_TargetInterface $target, $name)
    {
        $resource = null;
        foreach ($this->containers as $container) {
            $legacyName = 'Legacy' . ucfirst($name);
            if ($container instanceof ServiceManager && $container->has($legacyName)) {
                $resource = $container->get($legacyName);
            } elseif(isset($container->$name)) {
                $resource = $container->$name;
            }

            if ($resource) {
                if ($target->answerRegistryRequest($name, $resource)) {
                    // Log that source has been found
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Removes a source container from this object.
     *
     * @param string $name The name to identify the container
     * @return \MUtil_Registry_Source
     */
    public function removeRegistryContainer($name)
    {
        unset($this->_containers[$name]);

        return $this;
    }
}