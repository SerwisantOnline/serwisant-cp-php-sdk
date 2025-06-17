<?php

namespace Serwisant\SerwisantCp\Traits;

use Serwisant\SerwisantApi\Types\SchemaPublic;
use Serwisant\SerwisantCp\ExceptionNotFound;

trait TokenRating
{
  public function rate()
  {
    $rateable = $this->getRateable();

    if (false === $rateable->isRateable) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }

    $rating_input = new SchemaPublic\RatingInput($this->request->get('rating', []));

    $result = $this->apiPublic()->publicMutation()->setRating((string)$this->token, $rating_input);

    if ($result->errors) {
      return $this->call($result->errors);
    } else {
      return $this->redirectTo(['token', ['id' => (string)$this->token]]);
    }
  }
}
