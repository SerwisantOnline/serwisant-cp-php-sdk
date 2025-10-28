<?php

namespace Serwisant\SerwisantCp;

use Adbar\Dot;
use Symfony\Component\Yaml\Yaml;

class YamlMap
{

  private $config;
  private $env;

  public function __construct($file, $env = null)
  {
    $this->env = $env;

    $tmp_dir = getenv('TMPDIR');
    if (trim($tmp_dir) == '') {
      $tmp_dir = sys_get_temp_dir();
    }
    $cache_file = $tmp_dir . '/serwisant_tr_cache_' . crc32(filemtime($file) . filesize($file)) . '_' . basename($file, ".yml");

    if (file_exists($cache_file)) {
      $data = unserialize(file_get_contents($cache_file));
    } else {
      $data = Yaml::parseFile($file);
      file_put_contents($cache_file, serialize($data));
    }

    $this->config = new Dot($data);
  }

  public function get($key)
  {
    if ($this->env) {
      $key = "{$this->env}.{$key}";
    }
    return $this->config->get($key);
  }

  public function all()
  {
    return $this->config->flatten();
  }
}
