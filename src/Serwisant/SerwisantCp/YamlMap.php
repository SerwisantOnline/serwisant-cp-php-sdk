<?php

namespace Serwisant\SerwisantCp;

use Noodlehaus\Config;

class YamlMap
{

  private $config;
  private $env;

  public function __construct($file, $env = null)
  {
    $this->env = $env;
    $this->config = new Config($file);
  }

  public function get($key)
  {
    if ($this->env) {
      $key = "{$this->env}.{$key}";
    }
    return $this->config->get($key);
  }
}
