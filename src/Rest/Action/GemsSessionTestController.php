<?php


namespace Gems\Rest\Action;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

class GemsSessionTestController implements MiddlewareInterface
{
    /**
     * @var array Config
     */
    protected $config;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $this->request = $request;
        $this->testConfigAvailability();

        $this->testCookie();

        $this->testSession();

        return new HtmlResponse('');
    }

    protected function error($message)
    {
        echo '<span style="color: red; display: inline-block; min-width: 15rem;">FAILED</span>';
        echo $message . "\n<br>";
    }

    protected function getApplicationEnvironment()
    {
        if (isset($config['gems_auth']['linked_gemstracker']['application_env'])) {
            $applicationEnv = $this->config['gems_auth']['linked_gemstracker']['application_env'];
        } else {
            $applicationEnv = $this->config['project']['environment'];
        }
        return $applicationEnv;
    }

    protected function getApplicationPath()
    {
        return $this->config['gems_auth']['linked_gemstracker']['root_dir'] . '/application';
    }

    protected function getProjectNameUc()
    {
        return ucfirst($this->config['gems_auth']['linked_gemstracker']['project_name']);
    }

    protected function getSessionName()
    {
        $gemsProjectNameUc = $this->getProjectNameUc();
        $applicationPath = $this->getApplicationPath();
        return $gemsProjectNameUc . '_' . md5($applicationPath) . '_SESSID';
    }

    protected function getSessionNamespace()
    {
        $projectName = $this->config['gems_auth']['linked_gemstracker']['project_name'];
        return 'gems.' . $projectName . '.session';
    }

    protected function notice($message)
    {
        echo '<span style="color: orange; display: inline-block; min-width: 15rem;">NOTICE</span>';
        echo $message . "\n<br>";
    }

    protected function startSession()
    {
        $applicationEnv = $this->getApplicationEnvironment();
        $sessionName = $this->getSessionName();

        $cookiePath = strtr(dirname($_SERVER['SCRIPT_NAME']), '\\', '/');
        if (isset($this->config['gems_auth']['linked_gemstracker']['cookie_path'])) {
            $cookiePath = $this->config['gems_auth']['linked_gemstracker']['cookie_path'];
        }

        $sessionOptions['name']            = $sessionName;
        $sessionOptions['cookie_path']     = $cookiePath;
        $sessionOptions['cookie_httponly'] = true;
        $sessionOptions['cookie_secure']   = ($applicationEnv == 'production') || ($applicationEnv === 'acceptance');
        \Zend_Session::start($sessionOptions);
    }

    protected function success($message)
    {
        echo '<span style="color: green; display: inline-block; min-width: 15rem;">SUCCESS</span>';
        echo $message . "\n<br>";
    }

    protected function testConfigAvailability()
    {
        if (!array_key_exists('gems_auth', $this->config)) {
            $this->error('No gems_auth section found in the config');
            return;
        }
        if (!array_key_exists('use_linked_gemstracker_session', $this->config['gems_auth'])) {
            $this->error('No use_linked_gemstracker_session setting found in the gems_auth config');
            return;
        }
        if ($this->config['gems_auth']['use_linked_gemstracker_session'] !== true) {
            $this->error('use_linked_gemstracker_session setting has been disabled in the gems_auth config');
            return;
        }
        if (!array_key_exists('linked_gemstracker', $this->config['gems_auth'])) {
            $this->error('No linked_gemstracker setting found in the gems_auth config');
            return;
        }
        if (!array_key_exists('project_name', $this->config['gems_auth']['linked_gemstracker'])) {
            $this->error('No project_name setting found in the gems_auth linked_gemstracker config');
            return;
        }
        if (empty($this->config['gems_auth']['linked_gemstracker']['project_name'])) {
            $this->error('project_name setting is empty in the gems_auth linked_gemstracker config');
            return;
        }

        if (!array_key_exists('root_dir', $this->config['gems_auth']['linked_gemstracker'])) {
            $this->error('No root_dir setting found in the gems_auth linked_gemstracker config');
            return;
        }
        if (empty($this->config['gems_auth']['linked_gemstracker']['root_dir'])) {
            $this->error('root_dir setting is empty in the gems_auth linked_gemstracker config');
            return;
        }
        if (!file_exists($this->config['gems_auth']['linked_gemstracker']['root_dir'])) {
            $this->error(
                sprintf("GemsTracker root dir not found on server the gems_auth linked_gemstracker config. Set as '%s'",
                    $this->config['gems_auth']['linked_gemstracker']['root_dir']
                )
            );
            return;
        }

        if (!isset($this->config['gems_auth']['linked_gemstracker']['application_env']) && !isset($config['project']['environment'])) {
            $this->error('No environment set in either gems_auth linked_gemstracker application_env or project environment');
            return;
        }

        $this->success('Config settings are OK');
        echo "\n<br>";
    }

    protected function testCookie()
    {
        $applicationPath = $this->getApplicationPath();
        if (!file_exists($applicationPath)) {
            $this->error(
                sprintf("GemsTracker application dir not found on server the gems_auth linked_gemstracker config. Set as '%s'",
                    $applicationPath
                )
            );
            return;
        }

        $sessionName = $this->getSessionName();

        if (empty($sessionName)) {
            $this->error('Session name should not be empty');
            return;
        }

        $cookies = $this->request->getCookieParams();

        if (empty($cookies)) {
            $this->error('No cookies were found!');
            return;
        }

        if (!isset($cookies[$sessionName])) {
            $this->error(
                sprintf("Session cookie '%s' could not be found. Check if cookie has already been set after Gems site visit. Found cookies: %s",
                    $sessionName,
                    join(', ', array_keys($cookies))
                )
            );
            return;
        }

        $this->success('Cookie settings are OK');
        echo "\n<br>";
    }

    protected function testSession()
    {
        $this->startSession();
        $sessionNamespace = $this->getSessionNamespace();

        if (!\Zend_Session::namespaceIsset($sessionNamespace)) {
            $this->error(sprintf('Session namespace %s is not set.', $sessionNamespace));
            return;
        }

        $session = $_SESSION[$sessionNamespace];

        if (!array_key_exists('user_role', $session)) {
            $this->error('user_role not set in session');
        }

        if ($session['user_role'] == 'nologin') {
            $this->error('No login found!');
            return;
        }

        //$session = new \Zend_Session_Namespace('gems.' . $gemsProjectName . '.session');

        /*echo get_class($session);

        if (!isset($session->user_role)) {
            $this->error('User role not found in session.)
        }*/

        $this->success('Session is OK');
        echo "\n<br>";
    }


}