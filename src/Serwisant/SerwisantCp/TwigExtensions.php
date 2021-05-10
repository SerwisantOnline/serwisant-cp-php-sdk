<?php

namespace Serwisant\SerwisantCp;

use Twig\Environment;
use Silex;

class TwigExtensions
{
  protected $twig;
  protected $app;
  protected $translator;

  public function __construct(Environment $twig, Silex\Application $app)
  {
    $this->twig = $twig;
    $this->app = $app;
    $this->translator = $app['tr'];
  }

  protected function t(array $keys)
  {
    $translated = $this->translator->t($this->app['locale'], ...$keys);
    $last = $keys[count($keys) - 1];
    if (!is_array($last) && strpos($last, '_html') !== false) {
      return new \Twig\Markup($translated, 'UTF-8');
    } else {
      return twig_escape_filter($this->twig, $translated);
    }
  }

  protected function t_with_fallback(array $keys, array $keys_fallback)
  {
    try {
      return $this->translator->translate($this->app['locale'], ...$keys);
    } catch (TranslatorException $ex) {
      return $this->translator->t($this->app['locale'], ...$keys_fallback);
    }
  }
}
