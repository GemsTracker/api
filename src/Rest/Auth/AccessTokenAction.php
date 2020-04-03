<?php


namespace Gems\Rest\Auth;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface as ServerMiddlewareInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Stream;


class AccessTokenAction implements ServerMiddlewareInterface
{
    /**
     * @var League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    public function __construct(AuthorizationServer $server)
    {
        $this->server = $server;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $response = new JsonResponse(null);

        try {
            return $this->server->respondToAccessTokenRequest($request, $response);
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($response);
        } catch (\Exception $exception) {
            $error = ['error' => $exception->getMessage()];
            return $response->withStatus(500)->withPayload(json_encode($error));
        }
    }
}
