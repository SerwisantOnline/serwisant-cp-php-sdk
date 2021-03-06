<?php

namespace Serwisant\SerwisantCp;

use Silex;
use Twig;
use Symfony\Component\HttpFoundation\Request;
use Serwisant\SerwisantApi;

class Action
{
  /**
   * @var Silex\Application
   */
  protected $app;

  /**
   * @var Request
   */
  protected $request;

  /**
   * @var Twig\Environment;
   */
  protected $twig;

  /**
   * @var Translator
   */
  protected $translator;

  /**
   * @var SerwisantApi\AccessToken
   */
  protected $access_token_customer;

  /**
   * @var SerwisantApi\AccessToken
   */
  protected $access_token_public;

  protected $api_customer;
  protected $api_public;

  public function __construct(Silex\Application $app, Request $request)
  {
    $this->app = $app;
    $this->request = $request;
    $this->twig = $app['twig'];
    $this->translator = $app['tr'];
    $this->access_token_customer = $app['access_token_customer'];
    $this->access_token_public = $app['access_token_public'];
  }

  protected function renderPage(string $template, array $vars = [], $require_user = true)
  {
    $inner_vars = [
      'pageTitle' => '',
      'locale' => $this->app['locale'],
      'locale_ISO' => explode('_', $this->app['locale'])[0]
    ];

    if ($require_user) {
      $inner_vars['me'] = $this->apiCustomer()->customerQuery()->viewer();
    }

    $inner_vars['innerHTML'] = $this->twig->render($template, $vars);

    return $this->twig->render('layout.html.twig', array_merge($inner_vars, $vars));
  }

  protected function redirectTo($binding, $flash_tr = null)
  {
    if ($flash_tr) {
      $this->flashMessage($this->t($flash_tr));
    }
    $url = $this->app['url_generator']->generate($binding);
    return $this->app->redirect($url);
  }

  protected function flashMessage($txt)
  {
    $this->app['flash']->addMessage($txt);
  }

  protected function flashError($txt)
  {
    $this->app['flash']->addError($txt);
  }

  protected function t(...$keys)
  {
    $args = array_merge([$this->app['locale']], $keys);
    return call_user_func_array([$this->translator, 't'], $args);
  }

  protected function apiCustomer()
  {
    if (is_null($this->api_customer)) {
      $this->api_customer = new Api($this->app, $this->request, $this->access_token_customer);
    }
    return $this->api_customer;
  }

  protected function apiPublic()
  {
    if (is_null($this->api_public)) {
      $this->api_public = new Api($this->app, $this->request, $this->access_token_public);
    }
    return $this->api_public;
  }
}