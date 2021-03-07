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
    return $this->translator->t($this->app['locale'], ...$keys);
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
