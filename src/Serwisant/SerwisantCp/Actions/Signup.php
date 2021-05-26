<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantApi\Types\SchemaPublic\CustomerInput;
use Serwisant\SerwisantApi\Types\SchemaPublic\CustomerAgreementInput;
use Serwisant\SerwisantApi\Types\SchemaPublic\CustomFieldValueInput;

class Signup extends Action
{
  public function new($errors = [])
  {
    $result = $this->apiPublic()->publicQuery()->newRequest()->setFile('newSignup.graphql')->execute();
    $vars = [
      'customFieldsDefinitions' => $result->fetch('customerCustomFields'),
      'agreementsDefinitions' => $result->fetch('customerAgreements'),
      'form_params' => $this->request->request,
      'errors' => $errors,
    ];
    return $this->renderPage('signup.html.twig', $vars, false);
  }

  public function create()
  {
    $customer = $this->request->get('customer', []);
    $customer['customFields'] = array_map(function ($f) {
      return new CustomFieldValueInput(['ID' => $f['ID'], 'value' => (array_key_exists('value', $f) ? $f['value'] : '')]);
    }, $customer['customFields']);
    $customer_input = new CustomerInput($customer);

    $agreements_input = array_map(function ($f) {
      return new CustomerAgreementInput(['ID' => $f['ID'], 'accepted' => (array_key_exists('accepted', $f) && $f['accepted'] == '1')]);
    }, $this->request->get('agreements', []));

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
    $result = $this->apiPublic()->publicMutation()->activateCustomer($token);
    if ($result->errors) {
      $vars = [
        'errors' => $result->errors
      ];
      return $this->renderPage('signup_confirmation_failure.html.twig', $vars, false);
    } else {
      return $this->redirectTo('new_session', 'flashes.signup_activated');
    }
  }
}