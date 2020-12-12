<?php
class Db {
  private $db_conn_handle;

  /**
   * --------------------------------------------------------------------------------
   *                          P R I V A T E   M E T H O D S
   * --------------------------------------------------------------------------------
   */
  private function sendQuery($sql, $exe) {
    $stmt = $this->db_conn_handle->prepare($sql);
    $stmt->execute($exe);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    return $stmt->fetchAll();
  }

  /**
   * --------------------------------------------------------------------------------
   *                           P U B L I C   M E T H O D S
   * --------------------------------------------------------------------------------
   */
  public function query() : array {
    $num = func_num_args();
    $arg = func_get_args();
    $sql = $arg[0];
    unset($arg[0]);
    $f = strtolower(explode(' ', $sql, 2)[0]);
    $exe = [];
    if ($f == 'select' || $f == 'update' || $f == 'insert') {
      if (isset($arg[1])) {
        if (is_array($arg[1])) $exe = $arg[1];
        else foreach($arg as $a) array_push($exe, $a);
      }
      return $this->sendQuery($sql, $exe);
    }
    return null;
  }

  /**
   * --------------------------------------------------------------------------------
   *                              C O N S T R U C T O R
   * --------------------------------------------------------------------------------
   */
  function __construct() {
    $iniArr = parse_ini_file(ROOT . 'dbc.ini', true);
    if (isset($iniArr[$_SERVER['HTTP_HOST']])) {
      $ini = [
        'hostname' => $iniArr[$_SERVER['HTTP_HOST']]['hostname'],
        'database' => $iniArr[$_SERVER['HTTP_HOST']]['database'],
        'username' => $iniArr[$_SERVER['HTTP_HOST']]['username'],
        'password' => $iniArr[$_SERVER['HTTP_HOST']]['password']
      ];
    } else throw new Exception('Undefined Database Connection Configuration - check dbc.ini');
    unset($iniArr);
    try {
      $this->db_conn_handle = new PDO(
        "mysql:host=" . $ini['hostname'] . ";dbname=" . $ini['database'],
        $ini['username'],
        $ini['password']
      );
      $this->db_conn_handle->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
      );
    } catch(PDOException $e) {
      die("Database Connection Failed: " . $e->getMessage());
    }
  }

  /**
   * --------------------------------------------------------------------------------
   *                               D E S T R U C T O R
   * --------------------------------------------------------------------------------
   */
  function __destruct() {
    //
    unset($this->db_conn_handle);
  }
}