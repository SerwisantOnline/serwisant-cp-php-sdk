<?php

namespace Serwisant\SerwisantCp;

use PDO;
use SessionHandlerInterface;

class SessionHandlerPDO implements SessionHandlerInterface
{
  private $connection_string;
  private $user;
  private $password;

  private $dbh;
  private $table;

  public function setConnectionConfig($connection_string, $user, $password)
  {
    $this->connection_string = $connection_string;
    $this->user = $user;
    $this->password = $password;
  }

  public function setTable($table)
  {
    $this->table = $table;
  }

  public function open($save_path, $session_name)
  {
    $this->dbh = new PDO(
      $this->connection_string,
      $this->user,
      $this->password,
      array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_FOUND_ROWS => true
      )
    );
    return true;
  }

  public function close()
  {
    $this->dbh = null;
    return true;
  }

  public function read($id)
  {
    $stmt = $this->dbh->prepare("SELECT data FROM {$this->table} WHERE id=:id");
    $stmt->execute(array(':id' => $id));

    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($session) {
      $ret = $session['data'];
    } else {
      $ret = '';
    }

    return $ret;
  }

  public function write($id, $data)
  {
    $stmt = $this->dbh->prepare("REPLACE INTO {$this->table} (id, data, timestamp) VALUES (:id, :data, :timestamp)");
    $ret = $stmt->execute(array(
      ':id' => $id,
      ':data' => $data,
      'timestamp' => time()
    ));

    return ($stmt->rowCount() > 0);
  }

  public function destroy($id)
  {
    $stmt = $this->dbh->prepare("DELETE FROM {$this->table} WHERE id=:id");
    $ret = $stmt->execute(array(
      ':id' => $id
    ));

    return ($stmt->rowCount() > 0);
  }

  public function gc($max)
  {
    $stmt = $this->dbh->prepare("DELETE FROM {$this->table} WHERE timestamp < :limit");
    $stmt->execute(array(':limit' => time() - intval($max)));

    return ($stmt->rowCount() > 0);
  }
}