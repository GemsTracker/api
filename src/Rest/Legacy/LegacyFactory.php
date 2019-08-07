<?php

namespace Gems\Rest\Legacy;

use Interop\Container\ContainerInterface;
use Gems\Rest\Legacy\LegacyCacheFactoryWrapper;
use Zalt\Loader\ProjectOverloader;
use Zend\ServiceManager\Factory\FactoryInterface;

use Gems_Loader as Loader;
use Gems_Project_ProjectSettings as ProjectSettings;
use Gems_Util as Util;
use Gems_Util_BasePath as Util_BasePath;
use Zend_Cache as Cache;
use Zend_Locale as Locale;
use Zend_Translate as Translate;
use Zend_Translate_Adapter as TranslateAdapter;


class LegacyFactory implements FactoryInterface
{
    protected $cacheFactoryWrapper;

    protected $config;

    protected $container;

    protected $init;

    /**
     * @var \Zalt\Loader\ProjectOverloader;
     */
    protected $loader;

    protected function init()
    {
        if (!$this->init) {
            defined('VENDOR_DIR') || define('VENDOR_DIR', GEMS_ROOT_DIR . '/vendor/');

            defined('GEMS_LIBRARY_DIR') || define('GEMS_LIBRARY_DIR', VENDOR_DIR . '/gemstracker/gemstracker');
            defined('MUTIL_LIBRARY_DIR') || define('MUTIL_LIBRARY_DIR', realpath(VENDOR_DIR . '/magnafacta/mutil/src'));

            if (!defined('APPLICATION_PATH')) {
                if (isset($this->config['project'], $this->config['project']['vendor'])) {
                    define('APPLICATION_PATH', VENDOR_DIR . $this->config['project']['vendor'] . '/application');
                } else {
                    define('APPLICATION_PATH', null);
                }
            }

            if (!defined('GEMS_PROJECT_NAME') && isset($this->config['project'], $this->config['project']['name'])) {
                define('GEMS_PROJECT_NAME', $this->config['project']['name']);
            }
            defined('GEMS_PROJECT_NAME_UC') || define('GEMS_PROJECT_NAME_UC', ucfirst(GEMS_PROJECT_NAME));
            $this->init = true;
        }
    }

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $this->container = $container;
        $this->config = $container->get('config');

        $this->init();

        $this->loader = $this->container->get('loader');

        switch ($requestedName) {
            case Loader::class:
            case Util::class:
            case Util_BasePath::class:
            case \Gems_Tracker::class:
            case \Gems_Events::class:
            case \Gems_Agenda::class:
            case \Gems_Model::class:
            case \Gems_Menu::class:
                $requestedName = $this->stripOverloader($requestedName);
                return $this->loader->create($requestedName, $this->loader, []);
                break;

            case ProjectSettings::class:
                $project = $this->getProjectSettings();
                return $project;
                break;

            case 'LegacyCurrentOrganization':
                return $this->getCurrentOrganization();
                break;
            case 'LegacyCurrentUser':
                return $this->getCurrentUser();
                break;

            case \Gems_AccessLog::class:
                return $this->getAccessLog();
                break;

            case \Zend_Acl::class:
                return $this->getAcl();
                break;

            case Cache::class:
                $cache = $this->getCache();
                return $cache;
                break;

            case Locale::class:
                $locale = new \Zend_Locale('en');
                \Zend_Registry::set('Zend_Locale', $locale);
                return $locale;
                break;

            case \Gems_Log::class:
                return $this->getLogger();
                break;

            case \Zend_Session_Namespace::class:
                return $this->getSession();
                break;

            case 'LegacyStaticSession':
                return $this->getStaticSession();
                break;

            case Translate::class:
                //$translateOptions = $this->getTranslateOptions();
                return $this->getTranslate();
                break;

            case TranslateAdapter::class:
                return $this->getTranslateAdapter();
                break;

            case \Zend_View::class:
                return $this->getView();
                break;
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

    protected function getAccessLog()
    {
        $cache = $this->container->get('LegacyCache');
        $db = $this->container->get('LegacyDb');
        $loader = $this->container->get('LegacyLoader');
        return $this->loader->create('AccessLog', $cache, $db, $loader);
    }

    protected function getAcl()
    {
        $cache = $this->container->get('LegacyCache');
        $roles = $this->loader->create('Roles', $cache);
        return $roles->getAcl();
    }

    protected function getCache()
    {
        $project = $this->container->get('LegacyProject');

        $useCache = $project->getCache();

        $cache       = null;
        $exists      = false;
        $cachePrefix = GEMS_PROJECT_NAME . '_';

        // Check if APC extension is loaded and enabled
        if ((\MUtil_Console::isConsole() && !ini_get('apc.enable_cli') && $useCache === 'apc') || (isset($this->testConsoleApc) && $this->testConsoleApc)) {
            // To keep the rest readable, we just fall back to File when apc is disabled on cli
            $useCache = "File";
        }
        if (($useCache === 'apc' && extension_loaded('apc') && ini_get('apc.enabled')) || (isset($this->testApc) && $this->testApc)) {
            $cacheBackend = 'Apc';
            $cacheBackendOptions = array();
            //Add path to the prefix as APC is a SHARED cache
            $cachePrefix .= md5(APPLICATION_PATH);
            $exists = true;
        } else {
            $cacheBackend = 'File';
            $cacheDir = GEMS_ROOT_DIR . "/data/cache/";
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

            //$cache = \Zend_Cache::factory('Core', $cacheBackend, $cacheFrontendOptions, $cacheBackendOptions);
            $cacheWrapper = $this->getCacheFactoryWrapper();
            $cache = $cacheWrapper->factory('Core', $cacheBackend, $cacheFrontendOptions, $cacheBackendOptions);
        } else {
            $cacheWrapper = $this->getCacheFactoryWrapper();
            $cache = $cacheWrapper->factory('Core', 'Static', array('caching' => false), array('disable_caching' => true));
            //$cache = \Zend_Cache::factory('Core', 'Static', array('caching' => false), array('disable_caching' => true));
        }

        \Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        \Zend_Translate::setCache($cache);
        \Zend_Locale::setCache($cache);

        return $cache;
    }

    protected function getCacheFactoryWrapper()
    {
        if (!$this->cacheFactoryWrapper) {
            return new LegacyCacheFactoryWrapper();
        }
        return $this->cacheFactoryWrapper;
    }

    public function setCacheFactoryWrapper($wrapper)
    {
        $this->cacheFactoryWrapper = $wrapper;
    }

    public function getCurrentOrganization()
    {
        $user = $this->getCurrentUser();
        $organization = $user->getCurrentOrganization();
        return $organization;
    }

    public function getCurrentUser()
    {
        $currentUserRepository = $this->container->get(CurrentUserRepository::class);
        //try {
            $currentUser = $currentUserRepository->getCurrentUser();
            return $currentUser;
        /*} catch(\Exception $e) {
            return null;
        }*/
    }

    protected function getEnvironment()
    {
        if (defined('APPLICATION_ENV')) {
            return APPLICATION_ENV;
        } elseif ($config = $this->container->get('config') && isset($config['project'], $config['project']['environment'])) {
            return $config['project']['environment'];
        } elseif ($env = getenv('APPLICATION_ENV')) {
            return $env;
        }

        return 'development';
    }

    protected function getLogger()
    {
        $project = $this->container->get('LegacyProject');
        $translateAdapter = $this->container->get('LegacyTranslateAdapter');
        $logger = \Gems_Log::getLogger();

        $logPath = GEMS_ROOT_DIR . '/var/logs';

        try {
            $writer = new \Zend_Log_Writer_Stream($logPath . '/errors.log');
        } catch (\Exception $exc) {
            // Try to solve the problem, otherwise fail heroically
            \MUtil_File::ensureDir($logPath);
            $writer = new \Zend_Log_Writer_Stream($logPath . '/errors.log');
        }

        $filter = new \Zend_Log_Filter_Priority($project->getLogLevel());
        $writer->addFilter($filter);
        $logger->addWriter($writer);

        return $logger;
    }

    protected function getProjectSettings()
    {
        $projectArray = $this->includeFile(GEMS_ROOT_DIR . '/config/project');

        $project = $this->loader->create('Project_ProjectSettings', $projectArray);

        /* Testing if the supplied projectSettings is a class is supported in Gemstracker, but not used. For now it's disabled.
        /*if ($projectArray instanceof \Gems_Project_ProjectSettings) {
            $project = $projectArray;
        } else {
            $project = $this->loader->create('Project_ProjectSettings', $projectArray);
        }*/

        return $project;
    }

    protected function getSession()
    {
        $config = $this->container->get('config');

        if (isset($config['gems_auth'])
            && isset($config['gems_auth']['use_linked_gemstracker_session'])
            && $config['gems_auth']['use_linked_gemstracker_session'] === true
            && isset($config['gems_auth']['linked_gemstracker'])
        ) {
            $gemsProjectNameUc = ucfirst($config['gems_auth']['linked_gemstracker']['project_name']);
            $applicationPath = $config['gems_auth']['linked_gemstracker']['root_dir'] . '/application';

            if (isset($config['gems_auth']['linked_gemstracker']['application_env'])) {
                $applicationEnv = $config['gems_auth']['linked_gemstracker']['application_env'];
            } else {
                $applicationEnv = $config['project']['environment'];
            }

            $cookiePath = strtr(dirname($_SERVER['SCRIPT_NAME']), '\\', '/');
            if (isset($config['gems_auth']['linked_gemstracker']['cookie_path'])) {
                $cookiePath = $config['gems_auth']['linked_gemstracker']['cookie_path'];
            }


            $sessionOptions['name']            = $gemsProjectNameUc . '_' . md5($applicationPath) . '_SESSID';
            $sessionOptions['cookie_path']     = $cookiePath;
            $sessionOptions['cookie_httponly'] = true;
            $sessionOptions['cookie_secure']   = ($applicationEnv == 'production') || ($applicationEnv === 'acceptance');
            \Zend_Session::start($sessionOptions);
        }

        $project = $this->container->get('LegacyProject');
        $session = new \Zend_Session_Namespace('gems.' . GEMS_PROJECT_NAME . '.session');

        $idleTimeout = $project->getSessionTimeOut();

        $session->setExpirationSeconds($idleTimeout);

        if (! isset($session->user_role)) {
            $session->user_role = 'nologin';
        }

        return $session;
    }

    protected function getStaticSession()
    {
        // Since userloading can clear the session, we put stuff that should remain (like redirect info)
        // in a different namespace that we call a 'static session', use getStaticSession to access.
        return new \Zend_Session_Namespace('gems.' . GEMS_PROJECT_NAME . '.sessionStatic');
    }

    protected function getTranslate()
    {
        $locale = $this->container->get('LegacyLocale');

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

        $translate = new \Zend_Translate($options);

        // If we don't find the needed language, use a fake translator to disable notices
        if (! $translate->isAvailable($language)) {
            $translate = \MUtil_Translate_Adapter_Potemkin::create();
        }
        return $translate;

        //Now if we have a project specific language file, add it
        $projectLanguageDir = APPLICATION_PATH . '/languages/';
        if (file_exists($projectLanguageDir)) {
            $options['content']        = $projectLanguageDir;
            $options['disableNotices'] = true;
            $projectTranslations       = new \Zend_Translate($options);
            //But only when it has the requested language
            if ($projectTranslations->isAvailable($language)) {
                $translate->addTranslation(array('content' => $projectTranslations));
            }
            unset($projectTranslations);  //Save some memory
        }

        $translate->setLocale($language);
        \Zend_Registry::set('Zend_Translate', $translate);

        return $translate;
    }

    protected function getTranslateAdapter()
    {
        $translate = $this->container->get(Translate::class);

        return $translate->getAdapter();
    }

    protected function getView()
    {
        $project = $this->container->get('LegacyProject');

        // Initialize view
        $view = new \Zend_View();
        $view->addHelperPath('MUtil/View/Helper', 'MUtil_View_Helper');
        $view->addHelperPath('MUtil/Less/View/Helper', 'MUtil_Less_View_Helper');
        $view->addHelperPath('Gems/View/Helper', 'Gems_View_Helper');
        $view->headTitle($project->getName());
        $view->setEncoding('UTF-8');

        $metas    = $project->getMetaHeaders();
        $headMeta = $view->headMeta();
        foreach ($metas as $httpEquiv => $content) {
            $headMeta->appendHttpEquiv($httpEquiv, $content);
        }

        $view->doctype(\Zend_View_Helper_Doctype::HTML5);

        // Add it to the ViewRenderer
        $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        // Return it, so that it can be stored by the bootstrap
        return $view;
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
                    return $config->toArray();
                    break;

                /*case 'xml':
                    $config = new \Zend_Config_Xml($fileName, $appEnvironment);
                    return $config->toArray();
                    break;*/

                case 'php':
                case 'inc':
                    // Exclude all variables not needed
                    unset($extension);

                    // All variables from this Escort file can be changed in the include file.
                    return include($fileName);
                    break;
            }
        }
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