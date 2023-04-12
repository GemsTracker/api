<?php

namespace Pulse\Api\Emma\Fhir\Repository;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class RespondentSsnSkipRepository
{
    protected Adapter $db;
    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    public function skipSsn($patientNr)
    {
        $sql = new Sql($this->db);
        $select = $sql->select();
        $select->from('gems__respondent_import_skip_ssn')
            ->columns(['griss_patient_nr'])
            ->where([
                'griss_patient_nr' => $patientNr
            ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if ($result->valid() && $result->current()) {
            return true;
        }

        return false;
    }
}