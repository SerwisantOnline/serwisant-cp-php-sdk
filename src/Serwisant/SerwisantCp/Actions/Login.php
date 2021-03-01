<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantApi;

class Login extends Action
{
  public function newSession()
  {
    return $this->renderPage('login.html.twig', [], false);
  }

  public function createSession()
  {
    $session_credentials = $this->request->get('session_credentials');
    try {
      $this->access_token->login($session_credentials['login'], $session_credentials['password']);
      return $this->app->redirect($this->urlTo('dashboard'));
    } catch (SerwisantApi\ExceptionUnauthorized $ex) {
      return $this->renderPage('login.html.twig', ['createSessionError' => $ex->getHandle()], false);
    }
  }

  public function destroySession()
  {
    $this->access_token->logout();
    return $this->app->redirect($this->urlTo('dashboard'));
  }
}