<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

class Lang extends Action
{
  public function update($lang)
  {
    if (!$this->app['tr']->isSupported($lang)) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
    $_SESSION['lang'] = $lang;
    return $this->redirectBack();
  }
}