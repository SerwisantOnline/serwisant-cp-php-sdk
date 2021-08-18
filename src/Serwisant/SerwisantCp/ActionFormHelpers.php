<?php

namespace Serwisant\SerwisantCp;

use Adbar;


class ActionFormHelpers
{
  // z "2021-05-30 11:29" na "2021-05-30T11:29:00+02:00"
  public function dateTimeToISO8601($date_time_str, $tz)
  {
    if (trim($date_time_str) == '') {
      return null;
    }
    $date = \DateTime::createFromFormat('Y-m-d H:m', $date_time_str);
    $date->setTimezone(new \DateTimeZone($tz));
    return $date->format('c');
  }

  public function mapAgreements($agreements_input)
  {
    if (!is_array($agreements_input)) {
      return [];
    }
    return array_map(function ($f) {
      $f = new Adbar\Dot($f);
      $a = ['customerAgreement' => $f->get('customerAgreement'), 'accepted' => ($f->get('accepted') == '1')];
      if ($f->get('ID')) {
        $a['ID'] = $f->get('ID');
        $klass = 'Serwisant\SerwisantApi\Types\SchemaCustomer\CustomerAgreementUpdateInput';
      } else {
        $klass = 'Serwisant\SerwisantApi\Types\SchemaPublic\CustomerAgreementInput';
      }
      return new $klass($a);
    }, $agreements_input);
  }

  public function mapCustomFields($custom_fields_input)
  {
    if (!is_array($custom_fields_input)) {
      return [];
    }
    return array_map(function ($f) {
      $f = new Adbar\Dot($f);
      $a = ['customField' => $f->get('customField'), 'value' => $f->get('value', '')];
      if ($f->get('ID')) {
        $a['ID'] = $f->get('ID');
        $klass = 'Serwisant\SerwisantApi\Types\SchemaCustomer\CustomFieldValueUpdateInput';
      } else {
        $klass = 'Serwisant\SerwisantApi\Types\SchemaCustomer\CustomFieldValueInput';
      }
      return new $klass($a);
    }, $custom_fields_input);
  }

  public function mapTemporaryFiles($temporary_files)
  {
    if (!is_array($temporary_files)) {
      return [];
    }
    return array_filter($temporary_files, function ($elem) {
      return trim($elem) != '';
    });
  }
}