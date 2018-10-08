<?php


namespace Gems\Rest\Legacy;


use Zalt\Loader\ProjectOverloader;

class ProjectOverloaderSourceContainer extends ProjectOverloader
{
    public function __get($name)
    {
        $legacyName = 'Legacy' . ucfirst($name);
        if ($this->serviceManager->has($legacyName)) {
            return $this->serviceManager->get($legacyName);
        }

        return null;
    }

    public function __isset($name)
    {
        $legacyName = 'Legacy' . ucfirst($name);
        return $this->serviceManager->has($legacyName);
    }
}