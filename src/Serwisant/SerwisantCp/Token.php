<?php

namespace Serwisant\SerwisantCp;

class Token
{
  private $token;
  private $subject_type;

  public function __construct($token = null, $subject_type = null)
  {
    $this->token = $token;
    $this->subject_type = $subject_type;
  }

  public function token()
  {
    return $this->token;
  }

  public function __toString()
  {
    return is_null($this->token) ? '' : $this->token;
  }

  public function subjectType()
  {
    return $this->subject_type;
  }
}