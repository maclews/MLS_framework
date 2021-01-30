<?php
class Auth {
  /**
   * --------------------------------------------------------------------------------
   *                       P R I V A T E   P R O P E R T I E S
   * --------------------------------------------------------------------------------
   */
  private $table_name;
  private $hash_cost;
  private $last_hash_cost_time;
  private $cookie_name;

  /**
   * --------------------------------------------------------------------------------
   *                              C O N S T R U C T O R
   * --------------------------------------------------------------------------------
   */
  function __construct() {
    $this->table_name = 'debug_user_list';
    $this->hash_cost = Gstatic::app()->db()->query("SELECT `value` FROM `settings` WHERE `name` = 'password_hash_cost'")[0]['value'];
    $this->last_hash_cost_time = Gstatic::app()->db()->query("SELECT `value` FROM `settings` WHERE `name` = 'last_hash_cost_time'")[0]['value'];
    $this->cookie_name = "sos_auth";
  }

  /**
   * --------------------------------------------------------------------------------
   *                               D E S T R U C T O R
   * --------------------------------------------------------------------------------
   */
  function __destruct() {
    unset(
      $this->table_name,
      $this->hash_cost,
      $this->last_hash_cost_time,
      $this->cookie_name
    );
  }

  /**
   * --------------------------------------------------------------------------------
   *                           P U B L I C   M E T H O D S
   * --------------------------------------------------------------------------------
   */
  public function check() : bool {   // retrun TRUE if user logged in
    if (isset($_COOKIE[$this->cookie_name]) && strpos($_COOKIE[$this->cookie_name], "|") !== false) {
      $data = explode("|", $_COOKIE[$this->cookie_name]);
      $hash = Gstatic::app()->db()->query("SELECT `cookie_hash` FROM `$this->table_name` WHERE `login` = :login", ['login' => $data[0]])[0]['cookie_hash'];
      return ($data[1] == $hash);
    } else return false;
  }
  public function login($name, $pass) : bool {    // return TRUE if login successful
    $pass_check_time_target = '';
    $pass_chech_time = time();
    $db_select = Gstatic::app()->db()->query("SELECT `password`, `failed_count`, `locked` FROM `$this->table_name` WHERE `login` = :login", [
      'login' => $name
    ])[0];
    $fail_count = (int)$db_select['failed_count'];
    $db_pass = $db_select['password'];
    $lock = (bool)$db_select['locked'];
    if ($lock) return false;
    $result = false;
    $cookie_hash = '';
    if (password_verify(Gstatic::preHashPassword($pass), $db_pass)) {
      $pass_check_time_target = 'last_good_log';
      $fail_count = 0;
      $result = true;
      $cookie_hash = hash('sha3-224', $db_pass.time());
      setcookie($this->cookie_name, $name.'|'.$cookie_hash);
    } else {
      $pass_check_time_target = 'last_failed_log';
      $fail_count++;
      if ($fail_count >= 3) $lock = true;
    }
    $cookie_hash_insert = ($cookie_hash != '') ? ', `cookie_hash` = :ch' : '';
    Gstatic::app()->db()->query("UPDATE `$this->table_name` SET `$pass_check_time_target` = :ts, `failed_count` = :fc, `locked` = :lock$cookie_hash_insert WHERE `login` = :login", [
      'ts' => $pass_chech_time,
      'fc' => $fail_count,
      'lock' => $lock,
      'ch' => $cookie_hash,
      'login' => $name
    ]);
    return $result;
  }
  public function newAccount() {
    //
  }

  /**
   * --------------------------------------------------------------------------------
   *                          P R I V A T E   M E T H O D S
   * --------------------------------------------------------------------------------
   */
  private function checkPassword() {
    //
  }
  private function setHashCost() {
    if (($this->last_hash_cost_time + (7*24*3600)) < time()) {
      $target_time = 400;   // ms
      $cost = 10;
      $time = microtime(true);
      $password = 'correct horse battery staple';
      password_hash(Gstatic::preHashPassword($password), PASSWORD_BCRYPT);
      $time = (microtime(true) - $time) * 1000;
      $target = $target_time * 0.8;   // dozwolone odstępstwo o 20%
      do {
        if ($time > $target) break;
        else {
          $time *= 2;
          $cost++;
        }
      } while ($cost <= 16);    // 16 rund = ponad 3 sekundy @ AD2021
      if ($cost > $this->hash_cost) {
        Gstatic::app()->db()->query("UPDATE `settings` SET `value` = :cost WHERE `name` = :name", [
          'cost' => $cost,
          'name' => 'password_hash_cost'
        ]);
        Gstatic::app()->db()->query("UPDATE `settings` SET `value` = :lasttime WHERE `name` = :name", [
          'lasttime' => time(),
          'name' => 'last_hash_cost_time'
        ]);
      }
    }
  }
}