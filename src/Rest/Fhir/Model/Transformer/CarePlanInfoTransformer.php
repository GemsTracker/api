<?php

namespace Gems\Rest\Fhir\Model\Transformer;


use Gems\Rest\Fhir\Endpoints;

class CarePlanInfoTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key=>$row) {
            $respondentTrackId = $row['gr2t_id_respondent_track'];
            $info = [];

            $trackfieldData = $this->getTrackfields($respondentTrackId);

            foreach($trackfieldData as $trackFieldRow) {
                if ($trackFieldRow['type'] == 'appointment') {
                    $infoRow = [
                        'name' => $trackFieldRow['gtf_field_name'],
                        'type' => 'appointmentField',
                        'value' => null,
                    ];
                    if ($trackFieldRow['gr2t2f_value'] !== null) {
                        $infoRow['value'] = [
                            'type' => 'Appointment',
                            'id' => (int)$trackFieldRow['gr2t2f_value'],
                            'reference' => Endpoints::APPOINTMENT . $trackFieldRow['gr2t2f_value'],
                        ];
                    }
                } else {
                    $infoRow = [
                        'name' => $trackFieldRow['gtf_field_name'],
                        'type' => 'trackField',
                        'value' => $trackFieldRow['gr2t2f_value'],
                    ];
                    if ($displayValue = $this->getDisplayValue($trackFieldRow)) {
                        $infoRow['display'] = $displayValue;
                    }
                }
                if (isset($trackFieldRow['gtf_field_code'])) {
                    $infoRow['code'] = $trackFieldRow['gtf_field_code'];
                }
                $info[] = $infoRow;
            }

            $data[$key]['supportingInfo'] = $info;
        }

        return $data;
    }

    protected function getCaretakerName($caretakerId)
    {
        $model = new \MUtil_Model_TableModel('gems__agenda_staff');
        $result = $model->loadFirst(['gas_id_staff' => $caretakerId]);
        if ($result) {
            return $result['gas_name'];
        }
        return null;
    }

    protected function getDisplayValue($trackFieldInfo)
    {
        switch ($trackFieldInfo['gtf_field_type']) {
            case 'caretaker':
                return $this->getCaretakerName($trackFieldInfo['gr2t2f_value']);
            case 'location':
                return $this->getLocationName($trackFieldInfo['gr2t2f_value']);
            default:
                return null;
        }
    }

    protected function getLocationName($locationId)
    {
        $model = new \MUtil_Model_TableModel('gems__locations');
        $result = $model->loadFirst(['glo_id_location' => $locationId]);
        if ($result) {
            return $result['glo_name'];
        }
        return null;
    }

    protected function getTrackfieldModel()
    {
        $unionModel = new \MUtil_Model_UnionModel('respondentTrackFieldData');

        $trackFieldsModel = new \Gems_Model_JoinModel('trackFieldData', 'gems__respondent2track', 'gr2t', false);
        $trackFieldsModel->addTable('gems__track_fields',
            [
                'gr2t_id_track' => 'gtf_id_track'
            ],
            'gtf',
            false
        );

        $trackFieldsModel->addLeftTable(
            'gems__respondent2track2field',
            [
                'gr2t_id_respondent_track' => 'gr2t2f_id_respondent_track',
                'gtf_id_field' => 'gr2t2f_id_field',
            ],
            'gr2t2f',
            false
        );

        $trackFieldsModel->addColumn(new \Zend_Db_Expr('\'field\''), 'type');

        $unionModel->addUnionModel($trackFieldsModel, null);

        $trackAppointmentsModel = new \Gems_Model_JoinModel('trackAppointmentData', 'gems__respondent2track', 'gr2t', false);
        $trackAppointmentsModel->addTable('gems__track_appointments',
            [
                'gr2t_id_track' => 'gtap_id_track'
            ],
            'gtf',
            false
        );

        $trackAppointmentsModel->addLeftTable(
            'gems__respondent2track2appointment',
            [
                'gr2t_id_respondent_track' => 'gr2t2a_id_respondent_track',
                'gtap_id_app_field' => 'gr2t2a_id_app_field',
            ],
            'gr2t2f',
            false
        );

        $trackAppointmentsModel->addColumn(new \Zend_Db_Expr('\'appointment\''), 'type');

        $trackAppointmentdMapBase = $trackAppointmentsModel->getItemsOrdered();
        $trackAppointmentdMap = array_combine($trackAppointmentdMapBase, str_replace(['gr2t2a_', 'gtap'], ['gr2t2f_', 'gtf'], $trackAppointmentdMapBase));
        $trackAppointmentdMap['gr2t2a_id_app_field'] = 'gr2t2f_id_field';
        $trackAppointmentdMap['gr2t2a_id_appointment'] = 'gr2t2f_value';
        $trackAppointmentdMap[] = 'type';

        $unionModel->addUnionModel($trackAppointmentsModel, $trackAppointmentdMap);

        return $unionModel;
    }

    protected function getTrackfields($respondentTrackId)
    {
        $model = $this->getTrackfieldModel();

        return $model->load(
            [
                'gr2t_id_respondent_track' => $respondentTrackId,
            ]
        );
    }
}
