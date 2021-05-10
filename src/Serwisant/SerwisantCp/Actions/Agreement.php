<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantApi\Types\SchemaPublic\CustomerAgreementsFilter;
use Serwisant\SerwisantCp\Action;

class Agreement extends Action
{
  public function call($id)
  {
    $filter = new CustomerAgreementsFilter();
    $filter->ID = $id;

    $agreements = $this->apiPublic()->publicQuery()->customerAgreements($filter);
    if (count($agreements) == 0) {
      return $this->notFound();
    }

    $vars = [
      'agreement' => $agreements[0]
    ];
    return $this->renderPage('agreements.html.twig', $vars, false);
  }
}