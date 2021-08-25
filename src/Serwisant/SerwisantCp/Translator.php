<?php

namespace Serwisant\SerwisantCp;

class Translator
{
  const CURRENCIES = [
    ['locale' => 'en_US', 'code' => 'USD', 'symbol' => '$'],
    ['locale' => 'pl_PL', 'code' => 'PLN', 'symbol' => 'zł'],
  ];

  const PHONE_PREFIXES = [
    'en_US' => '1',
    'pl_PL' => '48',
  ];

  private $translations = [];
  private $default_locale;

  /**
   * Translator constructor.
   * @param array $translations_yaml_files
   * @param string $default_locale
   */
  public function __construct(array $translations_yaml_files, $default_locale = 'en_US')
  {
    foreach ($translations_yaml_files as $file) {
      $this->translations[] = new YamlMap($file);
    }
    $this->default_locale = $default_locale;
  }

  /**
   * @param $code
   * @return string
   * @throws TranslatorException
   */
  public function codeToCurrencySymbol($code)
  {
    foreach (self::CURRENCIES as $currency) {
      if ($currency['code'] == $code) {
        return $currency['symbol'];
      }
    }
    throw new TranslatorException("Currency code {$code} not supported");
  }

  /**
   * @param null $locale
   * @return string
   * @throws TranslatorException
   */
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
    throw new TranslatorException("Locale {$locale} not supported");
  }

  public function localeToPhonePrefix($locale = null)
  {
    if (!$locale) {
      $locale = $this->default_locale;
    }
    if (array_key_exists($locale, self::PHONE_PREFIXES)) {
      return self::PHONE_PREFIXES[$locale];
    }
    throw new TranslatorException("Locale {$locale} not supported");
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
}