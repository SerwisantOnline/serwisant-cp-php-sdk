<?php

namespace Serwisant\SerwisantCp;

use PragmaRX\Countries\Package\Services\Helper;

class CountryDatabaseHelper extends Helper
{
  public function loadJson($file, $dir = null)
  {
    $cache_key = crc32(filemtime($file) . filesize($file)) . '_' . basename($file, ".json");
    $data = StaticCache::getInstance()->load($cache_key);
    if (!$data) {
      $data = parent::loadJson($file, $dir);
      StaticCache::getInstance()->save($cache_key, $data);
    }
    return $data;
  }
}