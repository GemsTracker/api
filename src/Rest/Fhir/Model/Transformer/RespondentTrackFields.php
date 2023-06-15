<?php

namespace Gems\Rest\Fhir\Model\Transformer;

trait RespondentTrackFields
{
    protected function getDisplayValue($trackFieldInfo)
    {
        if ($trackFieldInfo['gr2t2f_value'] === null) {
            return null;
        }
        switch ($trackFieldInfo['gtf_field_type']) {
            case 'caretaker':
                return $this->getCaretakerName($trackFieldInfo['gr2t2f_value']);
            case 'location':
                return $this->getLocationName($trackFieldInfo['gr2t2f_value']);
            default:
                return null;
        }
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

    protected function getLocationName($locationId)
    {
        $model = new \MUtil_Model_TableModel('gems__locations');
        $result = $model->loadFirst(['glo_id_location' => $locationId]);
        if ($result) {
            return $result['glo_name'];
        }
        return null;
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
        $trackFieldsModel->addColumn(new \Zend_Db_Expr('CONCAT(\'f__\', gtf_id_field)'), 'id');

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
        $trackAppointmentsModel->addColumn(new \Zend_Db_Expr('CONCAT(\'a__\', gtap_id_app_field)'), 'id');
        $trackAppointmentsModel->addColumn(new \Zend_Db_Expr('\'appointment\''), 'gtf_field_type');

        $trackAppointmentdMapBase = $trackAppointmentsModel->getItemsOrdered();
        $trackAppointmentdMap = array_combine($trackAppointmentdMapBase, str_replace(['gr2t2a_', 'gtap'], ['gr2t2f_', 'gtf'], $trackAppointmentdMapBase));
        $trackAppointmentdMap['gr2t2a_id_app_field'] = 'gr2t2f_id_field';
        $trackAppointmentdMap['gr2t2a_id_appointment'] = 'gr2t2f_value';
        $trackAppointmentdMap[] = 'type';
        $trackAppointmentdMap[] = 'id';

        $unionModel->addUnionModel($trackAppointmentsModel, $trackAppointmentdMap);

        return $unionModel;
    }
}
