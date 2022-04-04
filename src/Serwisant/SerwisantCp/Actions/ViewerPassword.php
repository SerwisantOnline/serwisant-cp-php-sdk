<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantApi\Types\SchemaCustomer\CustomerUpdateInput;
use Serwisant\SerwisantApi\Types\SchemaCustomer\AddressUpdateInput;

use Serwisant\SerwisantCp\Action;

class ViewerPassword extends Action
{
  public function edit($errors = [])
  {
    $vars = [
      'form_params' => $this->request->request,
      'errors' => $errors,
      'js_files' => ['viewer_password_edit.js'],
      'pageTitle' => $this->t('viewer_password_edit.title'),
    ];
    return $this->renderPage('viewer_password_edit.html.twig', $vars);
  }

  public function update()
  {
    $user = $this->request->get('user', []);
    $result = $this->apiCustomer()->customerMutation()->updateViewerPassword($user['currentPassword'], $user['password'], $user['password']);

    if ($result->errors) {
      return $this->edit($result->errors);
    } else {
      $this->accessTokenCustomer()->revoke();
      return $this->redirectTo('dashboard', 'flashes.viewer_password_update_successful');
    }
  }
}
