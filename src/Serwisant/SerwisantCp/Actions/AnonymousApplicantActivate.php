<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

class AnonymousApplicantActivate extends Action
{
  public function call($token)
  {
    $result = $this->apiPublic()->publicMutation()->activateAnonymousApplicant($token);
    if ($result->token) {
      return $this->redirectTo(['token', ['token' => $result->token]], 'flashes.anonymous_applicant_activated');
    } else {
      return $this->redirectTo('token', 'flashes.anonymous_applicant_activated');
    }
  }
}