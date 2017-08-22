<?php

namespace Gems\Rest\Legacy;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

use Gems_Loader as Loader;
use Gems_Project_ProjectSettings as ProjectSettings;
use Gems_Util as Util;
use Gems_Util_BasePath as Util_BasePath;
use Zend_Cache as Cache;
use Zend_Locale as Locale;
use Zend_Translate as Translate;

class LegacyFactory implements FactoryInterface
{

    protected $config;

    protected $container;

    protected $loader;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $this->container = $container;
        $this->loader = $this->container->get('loader');
        switch ($requestedName) {
            case Loader::class:
            case Util::class:
            case Util_BasePath::class:
                return $this->loader->create($requestedName, $container, []);
                break;

            case ProjectSettings::class:
                $this->config = $container->get('config');
                $project = $this->getProjectSettings();
                return $project;
                break;

            case Cache::class:
                $cache = $this->getCache();
                return $cache;
                break;

            case Locale::class:
                //return $this->loader->create('Locale', 'en');
                return new \Zend_Locale('en');
                break;

            case Translate::class:
                //$translateOptions = $this->getTranslateOptions();
                return $this->getTranslate();
        }

        return null;
    }

    private function findExtension($fullFileName, array $extensions)
    {
        foreach ($extensions as $extension) {
            if (file_exists($fullFileName . '.' . $extension)) {
                return $extension;
            }
        }
    }

    protected function getCache()
    {
        $project = $this->container->get('Legacyproject');

        $useCache = $project->getCache();

        $cache       = null;
        $exists      = false;
        $cachePrefix = GEMS_PROJECT_NAME . '_';

        // Check if APC extension is loaded and enabled
        if (\MUtil_Console::isConsole() && !ini_get('apc.enable_cli') && $useCache === 'apc') {
            // To keep the rest readable, we just fall back to File when apc is disabled on cli
            $useCache = "File";
        }
        if ($useCache === 'apc' && extension_loaded('apc') && ini_get('apc.enabled')) {
            $cacheBackend = 'Apc';
            $cacheBackendOptions = array();
            //Add path to the prefix as APC is a SHARED cache
            $cachePrefix .= md5(APPLICATION_PATH);
            $exists = true;
        } else {
            $cacheBackend = 'File';
            $cacheDir = GEMS_ROOT_DIR . "/var/cache/";
            $cacheBackendOptions = array('cache_dir' => $cacheDir);
            if (!file_exists($cacheDir)) {
                if (@mkdir($cacheDir, 0777, true)) {
                    $exists = true;
                }
            } else {
                $exists = true;
            }
        }

        if ($exists && $useCache <> 'none') {
            /**
             * automatic_cleaning_factor disables automatic cleaning of the cache and should get rid of
             *                           random delays on heavy traffic sites with File cache. Apc does
             *                           not support automatic cleaning.
             */
            $cacheFrontendOptions = array('automatic_serialization' => true,
                'cache_id_prefix' => $cachePrefix,
                'automatic_cleaning_factor' => 0);

            $cache = \Zend_Cache::factory('Core', $cacheBackend, $cacheFrontendOptions, $cacheBackendOptions);
        } else {
            $cache = \Zend_Cache::factory('Core', 'Static', array('caching' => false), array('disable_caching' => true));
        }

        \Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        \Zend_Translate::setCache($cache);
        \Zend_Locale::setCache($cache);

        return $cache;
    }


    protected function getEnvironment()
    {
        if (isset($this->config['project']) && isset($this->config['project']['environment'])) {
            return $this->config['project']['environment'];
        } else {
            return 'development';
        }
    }

    protected function getProjectSettings()
    {
        $projectArray = $this->includeFile(GEMS_ROOT_DIR . '/config/project');

        if ($projectArray instanceof \Gems_Project_ProjectSettings) {
            $project = $projectArray;
        } else {
            $project = $this->loader->create('Project_ProjectSettings', $projectArray);
        }

        return $project;
    }

    protected function getTranslate()
    {
        $locale = $this->container->get('Legacylocale');

        //echo get_class($locale);
        //die;

        $language = $locale->getLanguage();

        /*
         * Scan for files with -<languagecode> and disable notices when the requested
         * language is not found
         */
        $options = array(
            'adapter'         => 'gettext',
            'content'         => GEMS_LIBRARY_DIR . '/languages/',
            'disableNotices'  => true,
            'scan'            => \Zend_Translate::LOCALE_FILENAME);

        $translate = \MUtil_Translate_Adapter_Potemkin::create();

        return $translate;
    }

    /**
     * Searches and loads ini, xml, php or inc file
     *
     * When no extension is specified the system looks for a file with the right extension,
     * in the order: .ini, .php, .xml, .inc.
     *
     * .php and .inc files run within the context of this object and thus can access all
     * $this-> variables and functions.
     *
     * @param string $fileName A filename in the include path
     * @return mixed false if nothing was returned
     */
    protected function includeFile($fileName)
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if (!$extension) {
            $extension = $this->findExtension($fileName, array('inc', 'ini', 'php', 'xml'));
            $fileName .= '.' . $extension;
        }

        if (file_exists($fileName)) {
            $appEnvironment = $this->getEnvironment();
            switch ($extension) {
                case 'ini':
                    $config = new \Zend_Config_Ini($fileName, $appEnvironment);
                    break;

                case 'xml':
                    $config = new \Zend_Config_Xml($fileName, $appEnvironment);
                    break;

                case 'php':
                case 'inc':
                    // Exclude all variables not needed
                    unset($extension);

                    // All variables from this Escort file can be changed in the include file.
                    return include($fileName);
                    break;

                default:
                    throw new \Zend_Application_Exception(
                        'Invalid configuration file provided; unknown config type ' . $extension
                    );

            }

            return $config->toArray();
        }
    }
}