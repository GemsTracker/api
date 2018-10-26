<?php


namespace Pulse\Api\Action;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class EnvTestController implements MiddlewareInterface
{
    protected $project;

    public function __construct(\Gems_Project_ProjectSettings $project)
    {
        $this->project = $project;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        return new JsonResponse(['Environment' => APPLICATION_ENV, 'bounce' => $this->project->getEmailBounce()]);
    }
}