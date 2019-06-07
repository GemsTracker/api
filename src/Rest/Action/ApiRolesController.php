<?php


namespace Gems\Rest\Action;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;

class ApiRolesController implements MiddlewareInterface
{
    /**
     * @var TemplateRendererInterface
     */
    protected $template;

    public function __construct(TemplateRendererInterface $template)
    {
        $this->template = $template;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $data = [];

        return new HtmlResponse($this->template->render('app::api-roles', $data));

    }
}