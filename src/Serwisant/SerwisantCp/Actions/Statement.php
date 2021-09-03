<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantApi\Types\SchemaPublic\CustomerAgreementsFilter;
use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

class Statement extends Action
{
  public function show($id)
  {
    $statement = null;
    foreach ($this->apiPublic()->publicQuery()->customerStatements() as $s) {
      if ($s->ID == $id) {
        $statement = $s;
        break;
      }
    }

    if (is_null($statement)) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }

    return $this->renderPage('statements.html.twig', ['statement' => $statement]);
  }
}