<?php

namespace Pulse\Api\Model;
use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;
use Gems\Rest\Fhir\Model\Transformer\IntTransformer;

/**
 * TEMPORARY, DO NOT ADD TO GIT, WILL BE REPLACED IN PULSE
 */
class ActivityDiagnosisModel extends \Gems_Model_JoinModel
{
    /**
     * @var \Gems_Util
     */
    protected $util;

    public function __construct()
    {
        parent::__construct('activity2Diagnosis', 'pulse__activity2diagnosis', 'ga2d', true);
    }

    public function afterRegistry()
    {
        $this->set('pa2d_id_activity2diagnosis', [
            'label' => $this->_('ID'),
            'elementClass' => 'hidden',
            'apiName' => 'id',
        ]);

        $this->set('pa2d_activity', [
            'label' => $this->_('Activity'),
            'description' => $this->_('Use %-sign for wildcard'),
            'elementClass' => 'textSuggestions',
            'apiName' => 'activity',
            'multiOptionSettings' => [
                'reference' => 'agenda-activities',
                'column' => 'name',
            ],
        ]);

        $this->set('medicalCategory', [
            'label' => $this->_('Medical category'),
            'apiName' => 'medicalCategory',
            'elementClass' => 'select',
            'no_text_search' => true,
            'multiOptionSettings' => [
                'reference' => 'ichom/medical-category',
                'key' => 'id',
                'value' => 'name',
            ],
        ]);

        $this->set('pa2d_id_diagnosis', [
            'label' => $this->_('Diagnosis'),
            'apiName' => 'diagnosis',
            'elementClass' => 'select',
            'multiOptionSettings' => [
                'reference' => 'ichom/diagnosis',
                'key' => 'id',
                'value' => 'name',
                'filter' => [
                    'otherFieldValueJoin' => [
                        'medicalCategory' => [
                            'medicalCategory',
                            'id',
                        ],
                    ],
                ],
            ],
        ]);

        $yesNo =  $this->util->getTranslated()->getYesNo();
        $this->set('pa2d_active', [
            'label' => $this->_('Active'),
            'multiOptions' => $yesNo,
            'elementClass' => 'Checkbox',
            'default' => true,
            'apiName' => 'active',
        ]);

        $this->set('pa2d_order', [
            'label' => $this->_('Order'),
            'apiName' => 'order',
            'elementClass' => 'Number',
        ]);

        \Gems_Model::setChangeFieldsByPrefix($this, 'pa2d');

        $this->addTransformer(new IntTransformer(['pa2d_id_activity2diagnosis', 'pa2d_id_diagnosis', 'pa2d_order']));
        $this->addTransformer(new BooleanTransformer(['pa2d_active']));
    }
}
