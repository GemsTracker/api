<?php


namespace Gems\Rest\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class LocaleMiddleware implements MiddlewareInterface
{
    /**
     * @var array application config array
     */
    protected $config;

    protected $fallbackLocale = 'en';

    /**
     * LocaleMiddleware constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $language = $request->getHeaderLine('Accept-Language');

        // Check if language is available?


        if (empty($language) && isset($this->config['project']['locale'], $this->config['project']['locale']['default'])) {
            $language = $this->config['project']['locale']['default'];
        }

        if (empty($language)) {
            $language = $this->fallbackLocale;
        }

        $this->config['project']['local']['default'] = $language;
        \Zend_Locale::setDefault($language);

        $response = $delegate->process($request);

        return $response->withAddedHeader('Content-Language', $language);
    }
}