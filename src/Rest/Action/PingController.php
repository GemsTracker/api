<?php


namespace Gems\Rest\Action;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

class PingController implements MiddlewareInterface
{
    public static $definition = [
        'topic' => 'Ping',
        'methods' => [
            'get' => [
                'responses' => [
                    200 => [
                        'message' => 'string',
                        'current-time' => 'date',
                    ],
                ],
            ],
        ],
    ];

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $now = new \DateTime();
        return new JsonResponse(
            [
                'message' => 'hello!',
                'current-time' => $now->format(\DateTime::ISO8601),
            ]
            , 200);
    }
}
