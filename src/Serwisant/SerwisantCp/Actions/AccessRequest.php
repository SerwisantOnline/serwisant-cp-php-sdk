<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

class AccessRequest extends Action
{
  public function ask($customer)
  {
    $this->checkModuleActive();
    return $this->renderPage('access_request_ask.html.twig', ['customer' => $customer]);
  }

  public function sendLink($customer)
  {
    $this->checkModuleActive();
    $this->apiPublic()->publicMutation()->requestCustomerAccess($customer);
    return $this->redirectTo('new_session', 'flashes.access_request_successful');
  }

  public function new($errors = [])
  {
    $result = $this->apiPublic()->publicQuery()->newRequest()->setFile('newSignup.graphql')->execute();
    $vars = [
      'agreementsDefinitions' => $result->fetch('customerAgreements'),
      'jwt_token' => $this->requestParam('jwt_token', ''),
      'suggested_login' => $this->requestParam('suggested_login', ''),
      'form_params' => $this->request->request,
      'errors' => $errors,
      'js_files' => ['access_request_new.js']
    ];

    return $this->renderPage('access_request_new.html.twig', $vars);
  }

  public function create()
  {
    $agreements_input = $this->formHelper()->mapAgreements($this->requestParam('agreements', []));
    $result = $this
      ->apiPublic()
      ->publicMutation()
      ->createCustomerAccess(
        $this->requestParam('jwt_token', ''),
        $this->requestParam('login', ''),
        $this->requestParam('password', ''),
        $agreements_input
      );

    if ($result->errors) {
      return $this->new($result->errors);
    } else {
      return $this->redirectTo('new_session', 'flashes.access_request_complete');
    }
  }

  private function checkModuleActive()
  {
    $this->checkPanelActive();
    if (false === $this->getLayoutVars()['configuration']->panelSignups) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
  }
}