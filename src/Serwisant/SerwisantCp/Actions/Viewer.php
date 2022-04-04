<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantApi\Types\SchemaCustomer\CustomerUpdateInput;
use Serwisant\SerwisantApi\Types\SchemaCustomer\AddressUpdateInput;

use Serwisant\SerwisantCp\Action;

class Viewer extends Action
{
  public function edit($errors = [])
  {
    $vars = [
      'customer' => $this->apiCustomer()->customerQuery()->viewer(['complete' => true])->customer,
      'form_params' => $this->request->request,
      'errors' => $errors,
      'js_files' => ['signup_new.js'],
      'pageTitle' => $this->t('viewer_edit.title'),
    ];

    return $this->renderPage('viewer_edit.html.twig', $vars);
  }

  public function update()
  {
    $customer = $this->request->get('customer', []);
    if (array_key_exists('customFields', $customer)) {
      $customer['customFields'] = $this->formHelper()->mapCustomFields($customer['customFields']);
    }

    $customer_input = new CustomerUpdateInput($customer);
    $agreements_input = $this->formHelper()->mapAgreements($this->request->get('agreements', []));
    $addresses_input = array_map(function ($a) {
      return new AddressUpdateInput($a);
    }, $this->request->get('addresses', []));

    $result = $this->apiCustomer()->customerMutation()->updateViewer($customer_input, $agreements_input, $addresses_input);

    if ($result->errors) {
      return $this->edit($result->errors);
    } else {
      return $this->redirectTo('dashboard', 'flashes.viewer_data_update_successful');
    }
  }
}