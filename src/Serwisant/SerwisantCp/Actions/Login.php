<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantApi;

class Login extends Action
{
  public function new()
  {
    $vars = [
      'formParams' => $this->request->request,
      'js_files' => ['/assets/login.js']
    ];
    return $this->renderPage('login.html.twig', $vars, false);
  }

  public function resolveCredential()
  {
    $login_credential = $this->request->get('login_credential', '');
    $result = $this->apiPublic()->publicQuery()->login($login_credential);
    return $this->app->json($result);
  }

  public function create()
  {
    $session_credentials = $this->request->get('session_credentials');
    try {
      $this->accessTokenCustomer()->login($session_credentials['login'], $session_credentials['password']);
      return $this->redirectTo('dashboard', 'flashes.login_successful');
    } catch (SerwisantApi\ExceptionUnauthorized $ex) {
      $vars = [
        'formParams' => $this->request->request,
        'js_files' => ['/assets/login.js']
      ];
      $this->flashError($this->t("flashes.login_error.{$ex->getHandle()}"));
      return $this->renderPage('login.html.twig', $vars, false);
    }
  }

  public function destroy()
  {
    $this->accessTokenCustomer()->logout();
    return $this->redirectTo('dashboard', 'flashes.logout_successful');
  }
}