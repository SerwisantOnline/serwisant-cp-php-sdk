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

  /**
   * @var SerwisantApi\Api
   */
  protected $api_customer;

  /**
   * @var SerwisantApi\Api
   */
  protected $api_public;

  /**
   * @var bool
   */
  protected $debug = false;

  public function __construct(Silex\Application $app, Request $request)
  {
    $this->app = $app;
    $this->request = $request;
    $this->twig = $app['twig'];
    $this->translator = $app['tr'];
    $this->access_token_customer = $app['access_token_customer'];
    $this->access_token_public = $app['access_token_public'];
    $this->debug = ($this->app['env'] == 'development');
  }

  protected function renderPage(string $template, array $vars = [], $require_user = true)
  {
    if ($this->debug) {
      error_log("Rendering {$template}");
    }
    $result = $this->apiPublic()->publicQuery()->newRequest()->setFile('layoutAction.graphql')->execute();
    $inner_vars = [
      'pageTitle' => '',
      'locale' => $this->app['locale'],
      'locale_ISO' => explode('_', $this->app['locale'])[0],
      'agreements' => $result->fetch('customerAgreements'),
      'subscriber' => $result->fetch('viewer')->subscriber,
    ];
    if ($require_user) {
      $inner_vars['me'] = $this->apiCustomer()->customerQuery()->viewer();
    }
    $inner_vars['innerHTML'] = $this->twig->render($template, array_merge($inner_vars, $vars));

    return $this->twig->render('layout.html.twig', array_merge($inner_vars, $vars));
  }

  protected function generateUrl($to, $to_params = [], $data = [])
  {
    $url = $this->app['url_generator']->generate($to, $to_params);
    if (count($data) > 0) {
      $url .= '?' . http_build_query($data);
    }
    return $url;
  }

  protected function translateErrors(array $errors)
  {
    $errors_translated = [];
    foreach ($errors as $error) {
      $error->message = $this->t('errors', $error->argument, $error->code);
      $errors_translated[] = $error;
    }
    return $errors_translated;
  }

  protected function redirectTo($binding, $flash_tr = null)
  {
    if ($flash_tr) {
      $this->flashMessage($this->t($flash_tr));
    }
    if (is_array($binding)) {
      $url = $this->generateUrl($binding[0], $binding[1]);
    } else {
      $url = $this->generateUrl($binding);
    }
    return $this->app->redirect($url);
  }

  protected function notFound()
  {
    throw new ExceptionNotFound;
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