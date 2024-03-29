<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantApi;

class Login extends Action
{
  public function new()
  {
    $this->checkModuleActive();

    $vars = [
      'formParams' => $this->request->request,
      'js_files' => ['login_new.js'],
      'pageTitle' => $this->t('login_new.title'),
    ];
    return $this->renderPage('login_new.html.twig', $vars);
  }

  public function resolveCredential()
  {
    $login_credential = $this->request->get('login_credential', '');
    $result = $this->apiPublic()->publicQuery()->login($login_credential);
    return $this->app->json($result);
  }

  public function create()
  {
    $this->checkModuleActive();

    $session_credentials = $this->request->get('session_credentials');
    try {
      $this->accessTokenCustomer()->login($session_credentials['login'], $session_credentials['password']);
      return $this->redirectTo('dashboard', 'flashes.login_successful');
    } catch (SerwisantApi\ExceptionUnauthorized $ex) {
      $this->flashError($this->t("flashes.login_error.{$ex->getHandle()}"));
      return $this->new();
    }
  }

  public function destroy()
  {
    $this->accessTokenCustomer()->revoke();
    return $this->redirectTo('dashboard', 'flashes.logout_successful');
  }

  private function checkModuleActive()
  {
    $this->checkPanelActive();
  }
}