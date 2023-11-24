<?php

namespace Serwisant\SerwisantCp;

use Symfony\Component\HttpFoundation\Request;

class ApiHttpHeaders
{
  private Request $request;

  public function __construct(Request $request)
  {
    $this->request = $request;
  }

  /**
   * @return array
   */
  public function get()
  {
    $headers = [];
    $headers['Accept-Language'] = $this->request->headers->get('Accept-Language');
    return $headers;
  }
}