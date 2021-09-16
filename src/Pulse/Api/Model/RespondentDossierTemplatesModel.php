<?php

namespace Pulse\Api\Model;


use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;
use Gems\Rest\Fhir\Model\Transformer\IntTransformer;

class RespondentDossierTemplatesModel extends \Pulse\Model\RespondentDossierTemplatesModel
{
    public function __construct()
    {
        parent::__construct();
        $this->addTransformer(new BooleanTransformer(['has_template']));
        $this->addTransformer(new IntTransformer(['gr2t_id_respondent_track']));
    }
}
