<?php

namespace Serwisant\SerwisantCp;

use PragmaRX\Countries\Package\Countries;

class Translator
{
  private array $translations = [];
  private string $default_language;
  private string $language = '';
  private array $available_languages = [];
  private array $currencies = [];

  /**
   * @param array $translations_yaml_files
   * @param string $default_language
   * @throws TranslatorException
   */
  public function __construct(array $translations_yaml_files, string $default_language = 'pl')
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

    if (!in_array($default_language, $this->available_languages)) {
      throw new TranslatorException("There is no translations for default locale: {$default_language}");
    }

    $this->default_language = $default_language;
  }

  /**
   * @param string $language
   * @return bool
   */
  public function isSupported(string $language): bool
  {
    return in_array(strtolower($language), $this->available_languages);
  }

  /**
   * @param string $language
   * @return $this
   */
  public function setLanguage(string $language): Translator
  {
    $this->language = strtolower($language);
    return $this;
  }

  /**
   * @param string $code
   * @return string
   */
  public function codeToCurrencySymbol(string $code): string
  {
    if (empty($this->currencies)) {
      $countries = CountryDatabase::getInstance();
      foreach ($countries->currencies() as $currency) {
        $this->currencies[$currency->get('iso.code')] = $currency->get('units.major.symbol');
      }
    }
    return $this->currencies[$code];
  }

  /**
   * @param mixed ...$args
   * @return string|string[]
   */
  public function t(...$args)
  {
    try {
      return $this->translate(...$args);
    } catch (TranslatorException $ex) {
      return $ex->getMessage();
    }
  }

  /**
   * @param mixed ...$args
   * @return string|string[]
   * @throws TranslatorException
   */
  public function translate(...$args)
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
    $key = $this->language() . '.' . implode('.', $parts);

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

  public function language()
  {
    $language = $this->language;
    if (!in_array($language, $this->available_languages)) {
      $language = $this->default_language; // musi istnieć tłumaczenie z tego języka, sprawdzamy to w konstruktorze
    }

    return $language;
  }
}