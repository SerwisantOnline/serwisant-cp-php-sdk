<?php

namespace Serwisant\SerwisantCp;

use Serwisant\SerwisantApi\Types\SchemaCustomer\CustomFieldValueInput;

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

  public function mapCustomFields($custom_fields_input)
  {
    if (!is_array($custom_fields_input)) {
      return [];
    }
    return array_map(function ($f) {
      return new CustomFieldValueInput(['ID' => $f['ID'], 'value' => (array_key_exists('value', $f) ? $f['value'] : '')]);
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