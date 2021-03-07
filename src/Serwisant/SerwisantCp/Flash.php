<?php

namespace Serwisant\SerwisantCp;

class Flash
{
  const MESSAGES = 'messages';
  const ERRORS = 'errors';

  public function addError($txt)
  {
    return $this->add(self::ERRORS, $txt);
  }

  public function anyErrors()
  {
    return $this->any(self::ERRORS);
  }

  public function getErrors()
  {
    return $this->get(self::ERRORS);
  }

  public function addMessage($txt)
  {
    return $this->add(self::MESSAGES, $txt);
  }

  public function anyMessages()
  {
    return $this->any(self::MESSAGES);
  }

  public function getMessages()
  {
    return $this->get(self::MESSAGES);
  }

  public function any($for)
  {
    return count($this->all($for)) > 0;
  }

  public function get($for)
  {
    $entries = $this->all($for);
    $this->unset($for);
    return $entries;
  }

  /**
   * @param $for
   * @return array|mixed
   */
  private function all($for)
  {
    $key = "flash_{$for}";
    return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : [];
  }

  /**
   * @param $for
   * @param $txt
   * @return $this
   * @throws Exception
   */
  private function add($for, $txt)
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      throw new Exception("PHP session not started, call session_start() before storing or restoring a token");
    }
    $key = "flash_{$for}";
    if (false == array_key_exists($key, $_SESSION)) {
      $_SESSION[$key] = [];
    }
    $_SESSION[$key][] = $txt;

    return $this;
  }

  /**
   * @param $for
   */
  private function unset($for)
  {
    if (count($this->all($for)) > 0) {
      $key = "flash_{$for}";
      unset($_SESSION[$key]);
    }
  }
}