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
    $translated = $this->translator->t(...$keys);
    $last = $keys[count($keys) - 1];
    if (!is_array($last) && strpos($last, '_html') !== false) {
      return new \Twig\Markup($translated, 'UTF-8');
    } else {
      return $translated;
    }
  }

  protected function t_with_fallback(...$trs)
  {
    foreach ($trs as $keys) {
      try {
        $tr = $this->translator->translate(...$keys);
        if (is_string($tr)) {
          return $tr;
        }
      } catch (TranslatorException $ex) {
        # nic, następna pętla
      }
    }

    # nie znaleziono żadnych tłumaczeń, wyświetl jakiś informacyjny komunikat
    return implode(' / ', array_map(function ($keys) {
      return implode('.', $keys);
    }, $trs));
  }
}
