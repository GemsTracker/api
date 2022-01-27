<?php

namespace Pulse\Api\Action;


use Gems\Rest\Action\RestControllerAbstract;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Predicate\Like;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Predicate\PredicateSet;
use Laminas\Db\Sql\Sql;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Pulse\Api\Repository\OtherPatientNumbersRepository;

class OtherPatientNumbersController extends RestControllerAbstract
{
    /**
     * @var Adapter
     */
    protected $db;

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    public function get(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $patientNr = $request->getAttribute('patientNr');
        $organizationId = $request->getAttribute('organizationId');

        $otherPatientNumbersRepository = new OtherPatientNumbersRepository($this->db);

        $pairs = $otherPatientNumbersRepository->getAllPatientNumbers($patientNr, $organizationId);

        return new JsonResponse($pairs);
    }
}
