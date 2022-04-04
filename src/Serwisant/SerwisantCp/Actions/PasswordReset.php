<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantApi\Types\SchemaPublic\PasswordResetSubject;
use Serwisant\SerwisantCp\Action;

class PasswordReset extends Action
{
  public function new(array $errors = [])
  {
    $vars = [
      'formParams' => $this->request->request,
      'errors' => $errors,
      'js_files' => ['password_reset_new.js']
    ];
    return $this->renderPage('password_reset_new.html.twig', $vars);
  }

  public function create()
  {
    $result = $this->apiPublic()->publicMutation()->resetPassword($this->request->get('loginOrEmail'), PasswordResetSubject::CUSTOMER);
    if ($result->errors) {
      return $this->new($result->errors);
    } else {
      return $this->redirectTo('new_session', 'flashes.password_reset_sent');
    }
  }

  public function newPassword(array $errors = [])
  {
    $vars = [
      'token' => $this->request->get('jwt_token'),
      'formParams' => $this->request->request,
      'errors' => $errors,
      'js_files' => ['password_reset_new_password.js']
    ];
    return $this->renderPage('password_reset_new_password.html.twig', $vars);
  }

  public function createPassword()
  {
    $result = $this->apiPublic()->publicMutation()->setPassword($this->request->get('resetToken'), $this->request->get('password'), $this->request->get('passwordConfirmation'));
    if ($result->errors) {
      return $this->newPassword($result->errors);
    } else {
      return $this->redirectTo('new_session', 'flashes.password_set');
    }
  }
}