<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantApi\Types\SchemaPublic\PasswordResetSubject;
use Serwisant\SerwisantCp\Action;

class PasswordReset extends Action
{
  public function newReset(array $errors = [])
  {
    $vars = [
      'formParams' => $this->request->request,
      'errors' => $errors
    ];
    return $this->renderPage('password_reset.html.twig', $vars, false);
  }

  public function createReset()
  {
    $result = $this->apiPublic()->publicMutation()->resetPassword($this->request->get('loginOrEmail'), PasswordResetSubject::CUSTOMER);
    if ($result->errors) {
      return $this->newReset($result->errors);
    } else {
      return $this->redirectTo('new_session', 'flashes.password_reset_sent');
    }
  }

  public function newPassword($token, array $errors = [])
  {
    $vars = [
      'token' => $token,
      'formParams' => $this->request->request,
      'errors' => $errors
    ];
    return $this->renderPage('password_set.html.twig', $vars, false);
  }

  public function createPassword()
  {
    $result = $this->apiPublic()->publicMutation()->setPassword($this->request->get('resetToken'), $this->request->get('password'), $this->request->get('passwordConfirmation'));
    if ($result->errors) {
      return $this->newPassword($this->request->get('resetToken'), $result->errors);
    } else {
      return $this->redirectTo('new_session', 'flashes.password_set');
    }
  }
}