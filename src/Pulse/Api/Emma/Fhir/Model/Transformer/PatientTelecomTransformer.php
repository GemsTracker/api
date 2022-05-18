<?php

namespace Pulse\Api\Emma\Fhir\Model\Transformer;


use Laminas\I18n\Validator\PhoneNumber;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Regex;

class PatientTelecomTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    protected $currentEmailUse = null;

    protected $preferredEmailUse = 'home';

    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        $this->currentEmailUse = null;
        $row['gr2o_email'] = null;
        $row['grs_phone_1'] = null;
        $row['grs_phone_2'] = null;
        $row['grs_phone_3'] = null;

        if (isset($row['telecom']) && is_array($row['telecom'])) {
            foreach($row['telecom'] as $telecomItem) {
                if (isset($telecomItem['system'], $telecomItem['value'], $telecomItem['use'])) {
                    if ($telecomItem['system'] === 'email') {
                        $validator = new EmailAddress();
                        if (!$validator->isValid($telecomItem['value'])) {
                            continue;
                        }
                        if ($this->currentEmailUse !== $this->preferredEmailUse) {
                            $row['gr2o_email'] = $telecomItem['value'];
                            $this->currentEmailUse = $telecomItem['use'];
                        }
                        continue;
                    }
                    if ($telecomItem['system'] === 'phone') {
                        $validator = new Regex(['pattern' => '/^(\+|\d)[0-9\s]{7,20}$/']);

                        /*
                         * specific phone number validation can only occur if country is known. Even then, a phone number isn't guaranteed
                         * $validator = new PhoneNumber(['country' => $row['grs_iso_country']);*/
                        if (!$validator->isValid($telecomItem['value'])) {
                            continue;
                        }
                        switch($telecomItem['use']) {
                            case 'home':
                                $row['grs_phone_1'] = $telecomItem['value'];
                                break;
                            case 'work':
                                $row['grs_phone_2'] = $telecomItem['value'];
                                break;
                            case 'mobile':
                                $row['grs_phone_3'] = $telecomItem['value'];
                                break;
                            default:
                                break;
                        }
                    }
                }
            }
        }

        return $row;
    }
}
