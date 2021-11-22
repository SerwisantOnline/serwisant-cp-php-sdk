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
    $this->config = new Dot(Yaml::parseFile($file));
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
