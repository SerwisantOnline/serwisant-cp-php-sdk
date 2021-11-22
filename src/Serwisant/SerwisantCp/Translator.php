<?php

namespace Serwisant\SerwisantCp;

use PragmaRX\Countries\Package\Countries;

class Translator
{
  private array $translations = [];
  private string $default_locale;
  private array $available_languages = [];
  private array $currencies = [];

  /**
   * @param array $translations_yaml_files
   * @param string $default_locale
   * @throws TranslatorException
   */
  public function __construct(array $translations_yaml_files, string $default_locale = 'pl_PL')
  {
    foreach ($translations_yaml_files as $file) {
      $map = new YamlMap($file);
      $keys = array_keys($map->all());
      if (count($keys) == 0) {
        throw new TranslatorException("Translation file {$file} is empty");
      }
      $language = explode('.', $keys[0]);
      if (count($language) < 2) {
        throw new TranslatorException("Translation file {$file} is malformed");
      }
      $this->available_languages[] = $language[0];
      $this->translations[] = $map;
    }

    if (!in_array($this->language($default_locale), $this->available_languages)) {
      throw new TranslatorException("There is no translations for default locale: {$default_locale}");
    }

    $this->default_locale = $default_locale;

    $countries = new Countries();
    foreach ($countries->currencies() as $currency) {
      $this->currencies[$currency->get('iso.code')] = $currency->get('units.major.symbol');
    }
  }

  /**
   * @param $code
   * @return string
   * @throws TranslatorException
   */
  public function codeToCurrencySymbol($code)
  {
    return $this->currencies[$code];
  }

  /**
   * @param null $locale
   * @param mixed ...$args
   * @return string|string[]
   */
  public function t($locale = null, ...$args)
  {
    try {
      return $this->translate($locale, ...$args);
    } catch (TranslatorException $ex) {
      return $ex->getMessage();
    }
  }

  /**
   * @param null $locale
   * @param mixed ...$args
   * @return string|string[]
   * @throws TranslatorException
   */
  public function translate($locale = null, ...$args)
  {
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
    $key = $this->language($locale) . '.' . implode('.', $parts);

    $tr = '';
    foreach ($this->translations as $translation) {
      $tr = $translation->get($key);
      if ($tr) {
        break;
      }
    }
    if (is_array($tr)) {
      return $tr;
    }
    if (trim($tr) === '') {
      throw new TranslatorException("Missing translation {$key}");
    } elseif (count($replacements) > 0) {
      foreach ($replacements as $token => $value) {
        $token = '%{' . $token . '}';
        $tr = str_replace($token, $value, $tr);
      }
    }
    return $tr;
  }

  private function language($locale)
  {
    if ($locale) {
      $language = explode('_', $locale)[0];
    } else {
      $language = explode('_', $this->default_locale)[0];
    }
    if (!in_array($language, $this->available_languages)) {
      $language = explode('_', $this->default_locale)[0];
    }
    return $language;
  }
}