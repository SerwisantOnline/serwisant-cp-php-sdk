<?php

namespace Serwisant\SerwisantCp;

use Silex;
use Symfony\Component\HttpFoundation\Request;

class Action
{
  protected $app;
  protected $request;
  protected $token;
  protected $decorator;
  protected $api_http_headers = [];
  protected $twig;
  protected $translator;
  protected $debug = false;

  private $access_token_customer;
  private $access_token_public;
  private $api_customer;
  private $api_public;
  private $layout_vars;

  public function __construct(Silex\Application $app, Request $request, Token $token)
  {
    $this->app = $app;
    $this->request = $request;
    $this->token = $token;

    if (isset($app['action_decorator'])) {
      $this->decorator = $app['action_decorator'];
    }
    if (isset($app['access_token_customer'])) {
      $this->api_http_headers = $app['api_http_headers'];
    }
    $this->twig = $app['twig'];
    $this->translator = $app['tr'];
    $this->debug = ($this->app['env'] == 'development');

    if (isset($app['access_token_customer'])) {
      $this->access_token_customer = $app['access_token_customer'];
      $this->access_token_customer->setHttpHeaders($this->api_http_headers);
    }
    if (isset($app['access_token_public'])) {
      $this->access_token_public = $app['access_token_public'];
      $this->access_token_public->setHttpHeaders($this->api_http_headers);
    }
  }

  protected function formHelper(): ActionFormHelpers
  {
    return new ActionFormHelpers();
  }

  protected function renderPage(string $template, array $vars = [])
  {
    if ($this->debug) {
      error_log("Rendering $template");
    }

    $inner_vars = [
      'pageTitle' => '',
      'token' => (string)$this->token,
      'currentAction' => array_slice(explode("\\", get_class($this)), -1)[0],
      'locale' => $this->app['locale'],
      'locale_ISO' => strtoupper(explode('_', $this->app['locale'])[1]),
      'locale_PhonePrefix' => $this->translator->localeToPhonePrefix($this->app['locale']),
      'isAuthenticated' => (!is_null($this->access_token_customer) && $this->access_token_customer->isAuthenticated()),
    ];

    $inner_vars = array_merge($inner_vars, $this->getLayoutVars());

    if ($this->decorator instanceof ActionDecorator) {
      $inner_vars = array_merge($inner_vars, $this->decorator->getLayoutVars($template));
    }

    if (!is_null($this->access_token_customer) && $this->access_token_customer->isAuthenticated()) {
      $inner_vars['me'] = $this->apiCustomer()->customerQuery()->viewer(['basic' => true]);
    }

    $inner_vars['innerHTML'] = $this->twig->render($template, array_merge($inner_vars, $vars));

    return $this->twig->render($this->getLayoutName($template), array_merge($inner_vars, $vars));
  }

  private function getLayoutName($template)
  {
    if ($this->decorator instanceof ActionDecorator && !is_null($this->decorator->getLayoutName($template))) {
      return $this->decorator->getLayoutName($template);
    } else {
      return 'layout.html.twig';
    }
  }

  protected function getLayoutVars(): array
  {
    if (is_null($this->layout_vars)) {
      if (is_null($this->apiPublic())) {
        $this->layout_vars = [];
      } else {
        $result = $this->apiPublic()->publicQuery()->newRequest()->setFile('layoutAction.graphql')->execute();
        $this->layout_vars = [
          'agreements' => $result->fetch('customerAgreements'),
          'statements' => $result->fetch('customerStatements'),
          'subscriber' => $result->fetch('viewer')->subscriber,
          'configuration' => $result->fetch('configuration'),
          'currency' => $result->fetch('configuration')->currency,
        ];
      }
    }
    return $this->layout_vars;
  }

  protected function getListLimit()
  {
    if ($this->decorator instanceof ActionDecorator && !is_null($this->decorator->getListLimit())) {
      return $this->decorator->getListLimit();
    } else {
      return 15;
    }
  }

  protected function checkPanelActive()
  {
    if (false === $this->getLayoutVars()['configuration']->panelEnabled) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
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
    $redirect_variables = [];
    if (isset($this->app['token'])) {
      $redirect_variables['token'] = (string)$this->app['token'];
    }

    if ($flash_tr) {
      $this->flashMessage($this->t($flash_tr));
    }
    if (is_array($binding)) {
      $url = $this->generateUrl($binding[0], array_merge($binding[1], $redirect_variables));
    } else {
      $url = $this->generateUrl($binding, $redirect_variables);
    }
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

  protected function accessTokenCustomer()
  {
    return $this->access_token_customer;
  }

  protected function apiCustomer()
  {
    if (is_null($this->api_customer) && !is_null($this->access_token_customer)) {
      $this->api_customer = new Api(
        $this->app,
        $this->access_token_customer,
        [$this->app['base_dir'] . '/queries/customer']
      );
      $this->api_customer->setHttpHeaders($this->api_http_headers);
    }
    return $this->api_customer;
  }

  protected function apiPublic()
  {
    if (is_null($this->api_public) && !is_null($this->access_token_public)) {
      $this->api_public = new Api(
        $this->app,
        $this->access_token_public,
        [$this->app['base_dir'] . '/queries/public']
      );
      $this->api_public->setHttpHeaders($this->api_http_headers);
    }
    return $this->api_public;
  }
}