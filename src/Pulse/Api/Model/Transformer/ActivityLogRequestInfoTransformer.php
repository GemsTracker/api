<?php

declare(strict_types=1);


namespace Pulse\Api\Model\Transformer;


use Pulse\Api\Repository\RequestRepository;

class ActivityLogRequestInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     * @var RequestRepository
     */
    protected $requestRepository;

    public function __construct(RequestRepository $requestRepository)
    {
        $this->requestRepository = $requestRepository;
    }

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        $row['gla_remote_ip'] = $this->requestRepository->getIp();
        $row['gla_method'] = $this->requestRepository->getMethod();

        return $row;
    }
}
