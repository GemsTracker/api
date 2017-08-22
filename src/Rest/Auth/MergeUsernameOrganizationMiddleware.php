<?php


namespace Gems\Rest\Auth;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class MergeUsernameOrganizationMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $body = $request->getParsedBody();
        if (isset($body['username'], $body['organization'])) {
            $body['username'] = $body['username'] . '@' . $body['organization'];
            unset($body['organization']);
        }
        return $delegate->process($request->withParsedBody($body));
    }
}