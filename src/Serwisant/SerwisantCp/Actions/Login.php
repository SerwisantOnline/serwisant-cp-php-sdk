<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantApi;

class Login extends Action
{
  public function newSession()
  {
    $vars = [
      'formParams' => $this->request->request,
    ];
    return $this->renderPage('login.html.twig', $vars, false);
  }

  public function createSession()
  {
    $session_credentials = $this->request->get('session_credentials');
    try {
      $this->access_token_customer->login($session_credentials['login'], $session_credentials['password']);
      return $this->redirectTo('dashboard', 'flashes.login_successful');
    } catch (SerwisantApi\ExceptionUnauthorized $ex) {
      $vars = [
        'formParams' => $this->request->request,
      ];
      $this->flashError($this->t("flashes.login_error.{$ex->getHandle()}"));
      return $this->renderPage('login.html.twig', $vars, false);
    }
  }

  public function destroySession()
  {
    $this->access_token_customer->logout();
    return $this->redirectTo('dashboard', 'flashes.logout_successful');
  }
}