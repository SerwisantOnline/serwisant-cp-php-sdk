<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantApi\Types\SchemaPublic\CustomerInput;
use Serwisant\SerwisantApi\Types\SchemaPublic\CustomerAgreementInput;

class Signup extends Action
{
  public function newSignup($customer = [], $agreements = [], $addresses = [], $errors = [])
  {

    var_dump($errors);

    $result = $this->apiPublic()->publicQuery()->newRequest()->setFile('newSignup.graphql')->execute();
    $vars = [
      'customFields' => $result->fetch('customerCustomFields'),
      'agreements' => $result->fetch('customerAgreements'),
      'errors' => $errors,
    ];
    return $this->renderPage('signup.html.twig', $vars, false);
  }

  public function createSignup()
  {
    $customer = $this->request->get('customer', []);
    $customer_input = new CustomerInput($customer);

    $agreements = $this->request->get('agreements', []);
    $agreements_input = array_map(function ($agreement) {
      new CustomerAgreementInput(['ID' => $agreement['ID'], 'accepted' => true]);
    }, $agreements);

    $addresses = [];
    $addresses_input = [];

    $result = $this->apiPublic()->publicMutation()->createCustomer($customer_input, $agreements_input, $addresses_input);

    if ($result->errors) {
      return $this->newSignup($customer, $agreements, $addresses, $result->errors);
    } else {
      return 'OK';
    }
  }
}