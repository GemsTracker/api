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

        $sql = new Sql($this->db);
        $subSelect = $sql->select('gems__respondent2org')
            ->columns(['gr2o_id_user'])
            ->where([
                'gr2o_patient_nr' => $patientNr,
                'gr2o_id_organization' => $organizationId,
            ]);

        $currentOrganizationPredicate = new Predicate();
        $currentOrganizationPredicate->equalTo('gr2o_id_organization', $organizationId);

        $select = $sql->select('gems__respondent2org')
            ->join('gems__organizations', 'gor_id_organization = gr2o_id_organization', [])
            ->join('gems__reception_codes', 'gr2o_reception_code = grc_id_reception_code', [])
            ->columns(['gr2o_id_organization', 'gr2o_patient_nr'])
            ->where([
                'grc_success' => 1,
                'gr2o_id_user' => $subSelect,
                new PredicateSet([
                    $currentOrganizationPredicate,
                    new Like('gor_accessible_by', '%'.(int)$organizationId.'%'),
                ], PredicateSet::COMBINED_BY_OR),
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        $fields = iterator_to_array($result);

        $pairs = array_column($fields, 'gr2o_patient_nr', 'gr2o_id_organization');

        return new JsonResponse($pairs);
    }
}
