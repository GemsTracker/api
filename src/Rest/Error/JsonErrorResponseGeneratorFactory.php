<?php

declare(strict_types=1);


namespace Gems\Rest\Error;


use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Stratigility\Utils;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class JsonErrorResponseGeneratorFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new JsonErrorResponseGenerator();
    }

    /*public function __invoke($e, ServerRequestInterface $request, ResponseInterface $response)
    {
        $statusCode = Utils::getStatusCode($e, $response);
        $data = [
            'error' => 'unknown_server_error',
            'message' => 'Unknown server error. Please contact API administrator',
        ];
        if (count($data)) {
            return new JsonResponse($data, $statusCode);
        }
        return new EmptyResponse($statusCode);
    }*/
}
