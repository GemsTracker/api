<?php

declare(strict_types=1);


namespace Pulse\Api\Emma\Fhir\Action;


use Gems\Rest\Action\ModelRestController;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;

class EmmaResourceAction extends ModelRestController
{
    public function put(ServerRequestInterface $request)
    {
        if ($this->checkContentType($request) === false) {
            return new EmptyResponse(415);
        }

        $parsedBody = json_decode($request->getBody()->getContents(), true);

        if (empty($parsedBody)) {
            return new EmptyResponse(400);
        }

        $row = $this->translateRow($parsedBody, true);

        return $this->saveRow($request, $row, false);
    }

    public function translateRow($row, $reversed = false)
    {
        $translatedRow = parent::translateRow($row, $reversed);


        return $row;
    }
}
