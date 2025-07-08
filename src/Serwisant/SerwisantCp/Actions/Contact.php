<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantApi\Types\SchemaPublic\CustomerAgreementsFilter;
use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

class Contact extends Action
{
  public function show()
  {
    return $this->renderPage('contact.html.twig');
  }
}