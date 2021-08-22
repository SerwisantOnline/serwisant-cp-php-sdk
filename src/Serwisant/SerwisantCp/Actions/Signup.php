<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantApi\Types\SchemaPublic\CustomerInput;
use Serwisant\SerwisantApi\Types\SchemaPublic\CustomerAgreementInput;
use Serwisant\SerwisantApi\Types\SchemaPublic\CustomFieldValueInput;
use Serwisant\SerwisantCp\ActionFormHelpers;

class Signup extends Action
{
  public function new($errors = [])
  {
    $this->checkModuleActive();

    $result = $this->apiPublic()->publicQuery()->newRequest()->setFile('newSignup.graphql')->execute();
    $vars = [
      'customFieldsDefinitions' => $result->fetch('customerCustomFields'),
      'agreementsDefinitions' => $result->fetch('customerAgreements'),
      'form_params' => $this->request->request,
      'errors' => $errors,
      'js_files' => ['/assets/signup_new.js']
    ];

    return $this->renderPage('signup_new.html.twig', $vars);
  }

  public function create()
  {
    $this->checkModuleActive();

    $customer = $this->request->get('customer', []);
    if (array_key_exists('customFields', $customer)) {
      $customer['customFields'] = $this->formHelper()->mapCustomFields($customer['customFields']);
    }

    $customer_input = new CustomerInput($customer);
    $agreements_input = $this->formHelper()->mapAgreements($this->request->get('agreements', []));
    $addresses_input = [];

    $result = $this->apiPublic()->publicMutation()->createCustomer($customer_input, $agreements_input, $addresses_input);

    if ($result->errors) {
      return $this->new($result->errors);
    } else {
      return $this->redirectTo('new_session', 'flashes.signup_successful');
    }
  }

  public function confirm($token)
  {
    $this->checkModuleActive();

    $result = $this->apiPublic()->publicMutation()->activateCustomer($token);
    if ($result->errors) {
      $vars = [
        'errors' => $result->errors
      ];
      return $this->renderPage('signup_confirmation_failure.html.twig', $vars);
    } else {
      return $this->redirectTo('new_session', 'flashes.signup_activated');
    }
  }

  private function checkModuleActive()
  {
    if (false === $this->getLayoutVars()['configuration']->caPanelSignups) {
      $this->notFound();
    }
  }
}