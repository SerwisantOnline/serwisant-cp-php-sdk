<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

use Serwisant\SerwisantApi\Types\SchemaCustomer\AutocompleteScope;

class Autocomplete extends Action
{
  public function vendor()
  {
    return $this->query(AutocompleteScope::VENDOR);
  }

  public function model()
  {
    return $this->query(AutocompleteScope::MODEL);
  }

  private function query($scope)
  {
    $result = $this
      ->apiCustomer()
      ->customerQuery()
      ->autocomplete($scope, $this->request->get('q', ''));

    return $this->app->json($result);
  }
}