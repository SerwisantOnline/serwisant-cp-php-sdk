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
    if (trim(getenv('TMPDIR'))) {
      $cache_file = getenv('TMPDIR') . '/serwisant_yaml_cache_' . crc32(filemtime($file) . filesize($file)) . '_' . basename($file, ".yml");
      if (file_exists($cache_file)) {
        $data = unserialize(file_get_contents($cache_file));
      } else {
        $data = Yaml::parseFile($file);
        file_put_contents($cache_file, serialize($data));
      }
    } else {
      $data = Yaml::parseFile($file);
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
