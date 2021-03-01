<?php

namespace Serwisant\SerwisantCp;

class Translator
{
  const CURRENCIES = [
    ['locale' => 'pl_PL', 'code' => 'PLN', 'symbol' => 'zł']
  ];

  private $translations = [];
  private $default_locale;

  public function __construct(array $translations_yaml_files, $default_locale = 'en_US')
  {
    foreach ($translations_yaml_files as $file) {
      $this->translations[] = new YamlMap($file);
    }
    $this->default_locale = $default_locale;
  }

  public function codeToCurrencySymbol($code)
  {
    foreach (self::CURRENCIES as $currency) {
      if ($currency['code'] == $code) {
        return $currency['symbol'];
      }
    }
    throw new Exception("Currency code {$code} not supported");
  }

  public function localeToCurrencySymbol($locale = null)
  {
    if (!$locale) {
      $locale = $this->default_locale;
    }
    foreach (self::CURRENCIES as $currency) {
      if ($currency['locale'] == $locale) {
        return $currency['symbol'];
      }
    }
    throw new Exception("Locale {$locale} not supported");
  }

  public function t($locale = null, ...$args)
  {
    if (!$locale) {
      $locale = $this->default_locale;
    }
    $parts = [];
    $replacements = [];
    foreach ($args as $arg) {
      if (is_array($arg)) {
        $replacements = $arg;
        continue;
      }
      $part = $arg;
      if (stripos($part, '.html.twig')) {
        $part = str_replace('.html.twig', '', $part);
        $part = str_replace('/', '.', $part);
      }
      $parts[] = $part;
    }
    $key = $locale . '.' . implode('.', $parts);

    $tr = '';
    foreach ($this->translations as $translation) {
      $tr = $translation->get($key);
      if ($tr) {
        break;
      }
    }

    if (trim($tr) === '') {
      $tr = $key;
    } elseif (count($replacements) > 0) {
      foreach ($replacements as $token => $value) {
        $token = '%{' . $token . '}';
        $tr = str_replace($token, $value, $tr);
      }
    }
    return $tr;
  }
}