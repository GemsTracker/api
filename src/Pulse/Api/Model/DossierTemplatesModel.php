<?php


namespace Pulse\Api\Model;


use Gems\DataSetMapper\Model\DataSetModel;
use Gems\Rest\Fhir\Model\Transformer\BooleanTransformer;
use Gems\Rest\Fhir\Model\Transformer\IntTransformer;
use Pulse\Api\Fhir\Model\Transformer\AddColumnFromSubModelTransformer;
use Pulse\Api\Model\Transformer\ToManyTransformer;
use Pulse\Form\Decorator\TemplateEditor;

class DossierTemplatesModel extends \Gems_Model_JoinModel
{
    /**
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * @var \Gems_Util
     */
    protected $util;

    public function __construct()
    {
        parent::__construct('dossierTemplates', 'gems__dossier_templates', 'gdot', true);
    }

    public function applySettings($detailed = true)
    {
        $this->set('gdot_name', [
            'label' => $this->_('Name'),
        ]);

        $empty = $this->util->getTranslated()->getEmptyDropdownArray();
        $this->set('gdot_id_data_set', [
            'label' => $this->_('Data set'),
            'description' => $this->_('The data set that supplies variables'),
            'multiOptions' => $empty + $this->getDataSets(),
        ]);

        $yesNo =  $this->util->getTranslated()->getYesNo();
        $this->set('gdot_active', [
            'label' => $this->_('Active'),
            'multiOptions' => $yesNo,
            'elementClass' => 'Checkbox',
        ]);

        $this->set('gdot_template', [
            'label' => $this->_('Template'),
            'elementClass' => 'Html',
        ]);

        \Gems_Model::setChangeFieldsByPrefix($this, 'gdot');
    }

    public function applyApiSettings()
    {
        $this->resetOrder();

        $this->set('gdot_id_dossier_template', [
            'apiName' => 'id',
            'elementClass' => 'hidden',
        ]);

        $this->set('gdot_name', [
            'label' => $this->_('Name'),
            'apiName' => 'name',
        ]);

        $empty = $this->util->getTranslated()->getEmptyDropdownArray();
        $this->set('gdot_id_data_set', [
            'label' => $this->_('Data set'),
            'description' => $this->_('The data set that supplies variables'),
            'multiOptions' => $empty + $this->getDataSets(),
            'apiName' => 'dataSet',
        ]);

        $this->set('medicalCategory', [
            'label' => $this->_('Medical category'),
            'apiName' => 'medicalCategory',
            'elementClass' => 'select',
            'multiOptionSettings' => [
                'reference' => 'ichom/medical-category',
                'key' => 'id',
                'value' => 'name',
            ],
        ]);

        $this->set('diagnosis', [
            'label' => $this->_('Diagnosis'),
            'apiName' => 'diagnosis',
            'elementClass' => 'MultiSelect',
            'multiOptionSettings' => [
                'reference' => 'ichom/diagnosis',
                'key' => 'id',
                'value' => 'name',
                'onChange' => [
                    'treatment' => [
                        'multiOptionSettings' => [
                            'referenceData' => 'treatments',
                        ],
                    ],
                ],
                'filter' => [
                    'otherFieldValueJoin' => [
                        'medicalCategory' => [
                            'medicalCategory',
                            'id',
                        ],
                    ],
                ],
            ],
            'disable' => [
                'otherField' => [
                    'treatment',
                    'length',
                    '>',
                    1,
                ],
            ],
        ]);

        $this->set('treatment', [
            'label' => $this->_('Treatment'),
            'apiName' => 'treatment',
            'elementClass' => 'MultiSelect',
            'multiOptionSettings' => [
                'reference' => 'ichom/treatment',
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
            'disable' => [
                'otherField' => [
                    'diagnosis',
                    'length',
                    '>',
                    1,
                ],
            ],
        ]);

        /*$this->set('gdot_id_diagnosis', [
            'label' => $this->_('Diagnosis'),
            'apiName' => 'diagnosis',
            'multiOptionSettings' => [
                'reference' => 'ichom/diagnosis',
                'key' => 'id',
                'value' => 'name',
                'onChange' => [
                    'treatment' => [
                        'multiOptionSettings' => [
                            'referenceData' => 'treatments',
                        ],
                    ],
                ],
            ],
        ]);

        $this->set('gdot_id_treatment', [
            'label' => $this->_('Treatment'),
            'apiName' => 'treatment',
            'multiOptionSettings' => [
                'reference' => 'ichom/treatment',
                'key' => 'id',
                'value' => 'name',
            ],
        ]);*/

        $yesNo =  $this->util->getTranslated()->getYesNo();
        $this->set('gdot_active', [
            'label' => $this->_('Active'),
            'multiOptions' => $yesNo,
            'elementClass' => 'Checkbox',
            'apiName' => 'active',
        ]);

        $this->set('gdot_template', [
            'label' => $this->_('Template'),
            'elementClass' => 'DossierTemplate',
            'apiName' => 'template',
        ]);

        $this->addTransformer(new BooleanTransformer(['gdot_active']));
    }

    public function applyDiagnosesTreatments()
    {
        $subName = 'diagnosesTreatments';
        $subModel = new \MUtil_Model_TableModel('gems__dossier_template2diagnosis_treatment', $subName);
        \Gems_Model::setChangeFieldsByPrefix($subModel, 'gdotdt', $this->currentUser->getUserId());
        $subModel->addTransformer(new IntTransformer([
            'gdotdt_id_diag_treatment_link',
            'gdotdt_id_template',
            'gdotdt_id_medical_category',
            'gdotdt_id_diagnosis',
            'gdotdt_id_treatment'
        ]));
        $subJoins = ['gdot_id_dossier_template' => 'gdotdt_id_template'];


        $trans = new ToManyTransformer(true);
        $trans->addModel($subModel, $subJoins);

        $this->addTransformer($trans);
        /*$this->set($subName, [
            'model' => $subModel,
            //'elementClass', 'FormTable',
            'type' => \MUtil_Model::TYPE_CHILD_MODEL,
            'elementClass' => 'None',
        ]);*/

        $this->addTransformer(new AddColumnFromSubModelTransformer($subName, [
            'diagnosis' => 'gdotdt_id_diagnosis',
            'treatment' => 'gdotdt_id_treatment',
            'medicalCategory' => 'gdotdt_id_medical_category',
        ], ['medicalCategory']));
    }

    protected function getDataSets()
    {
        $model = new DataSetModel();
        $dataSets = $model->load();

        return array_column($dataSets, 'gds_name','gds_id');
    }
}
