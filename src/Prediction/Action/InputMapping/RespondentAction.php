<?php


namespace Prediction\Action\InputMapping;


use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;

class RespondentAction implements MiddlewareInterface
{
    /**
     * @param \Gems_Util $util
     */
    protected $util;

    public function __construct(\Gems_Util $util)
    {
        $this->util = $util;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $translated = $this->util->getTranslated();

        $respondentData = [
            'age' => [
                'name' => 'Age',
            ],
            'birthday' => [
                'name' => 'Birthday',
            ],
            'gender' => [
                'name' => 'Gender',
                'options' => $translated->getGenders(),
            ],
        ];

        return new JsonResponse($respondentData, 200);
    }
}
