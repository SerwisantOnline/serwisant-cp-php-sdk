<?php

namespace Serwisant\SerwisantCp;

use Adbar\Dot;
use Symfony\Component\Yaml\Yaml;

class YamlMap
{

  protected Dot $config;

  protected ?string $env;

  public function __construct($file, $env = null)
  {
    $cache_key = crc32(filemtime($file) . filesize($file)) . '_' . basename($file, ".yml");
    $data = StaticCache::getInstance()->load($cache_key);
    if (!$data) {
      $data = Yaml::parseFile($file);
      StaticCache::getInstance()->save($cache_key, $data);
    }
    $this->env = $env;
    $this->config = new Dot($data);
  }

  public function env(): string
  {
    return $this->env;
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
