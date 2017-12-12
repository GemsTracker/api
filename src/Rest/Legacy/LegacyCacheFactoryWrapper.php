<?php


namespace Gems\Rest\Legacy;


class LegacyCacheFactoryWrapper
{
    /**
     * Factory
     *
     * @param mixed  $frontend        frontend name (string) or Zend_Cache_Frontend_ object
     * @param mixed  $backend         backend name (string) or Zend_Cache_Backend_ object
     * @param array  $frontendOptions associative array of options for the corresponding frontend constructor
     * @param array  $backendOptions  associative array of options for the corresponding backend constructor
     * @param boolean $customFrontendNaming if true, the frontend argument is used as a complete class name ; if false, the frontend argument is used as the end of "Zend_Cache_Frontend_[...]" class name
     * @param boolean $customBackendNaming if true, the backend argument is used as a complete class name ; if false, the backend argument is used as the end of "Zend_Cache_Backend_[...]" class name
     * @param boolean $autoload if true, there will no require_once for backend and frontend (useful only for custom backends/frontends)
     * @throws Zend_Cache_Exception
     * @return Zend_Cache_Core|Zend_Cache_Frontend
     */
    public function factory($frontend, $backend, $frontendOptions = array(), $backendOptions = array(), $customFrontendNaming = false, $customBackendNaming = false, $autoload = false)
    {
        return \Zend_Cache::factory($frontend, $backend, $frontendOptions, $backendOptions, $customFrontendNaming, $customBackendNaming, $autoload);
    }
}