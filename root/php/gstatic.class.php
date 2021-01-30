<?php
class Gstatic {
  private static $_app_handle = null;
  private static $_auth = null;
  private static $_stopWatch = [];
  private static $_debugMode = false;
  private static $_MSUB = [];
  private static $_user_token = null;
  /**
   * --------------------------------------------------------------------------------
   *                          P R I V A T E   M E T H O D S
   * --------------------------------------------------------------------------------
   */
  /**
   * --------------------------------------------------------------------------------
   *                           P U B L I C   M E T H O D S
   * --------------------------------------------------------------------------------
   */
  /// Main App set up and handle
  public static function app($hand = null) {
    if (Self::$_app_handle != null) {
      return Self::$_app_handle;
    } elseif ($hand != null) {
      Self::$_app_handle = $hand;
    } else {
      throw new Exception('App not initialized.');
    }
  }
  /// Authentication handle
  public static function auth() {
    if (Self::$_auth === null) Self::$_auth = new Auth();
    return Self::$_auth;
  }
  /// High-resolution stopwatch
  public static function stopWatch($id, $key, $reset = false) {
    if (isset(Self::$_stopWatch[$id])) {
      //
    } else {
      Self::$_stopWatch[$id] = [
        'key' => $key,
        'time' => hrtime()
      ];
    }
  }
  /// Terminate the Application
  public static function quit() {
    Gstatic::$_app_handle = null;
  }
  /// Submodule variables storage handle
  public static function mSubSet($var, $data) {
    $_SUB[$var] = $data;
  }
  public static function mSubGet($var) {
    return $_SUB[$var];
  }
  // OPENSSL
  public static function encryptString($string, $cipher = 'fast') {
    switch($cipher) {
      case 'fast':
        $cipher = 'aes-192-ecb';
        break;
      case 'best':
        $cipher = 'camellia-256-cfb1';
        break;
    }
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $iv16 = bin2hex($iv);
    $key = parse_ini_file(ROOT . 'ssl.ini')['encryptString'];
    $crypt = openssl_encrypt($string, $cipher, $key, $options = 0, hex2bin($iv16));
    $cryptArray = [
      $crypt,
      $iv16
    ];
    return $cryptArray;
  }
  //
  public static function preHashPassword($password) {
    return hash('whirlpool', $password);
  }
  public static function hashPassword($password, $cost = 10) {
    $temp = hash('whirlpool', $password);
    $options = ['cost' => $cost];
    $password = password_hash($temp, PASSWORD_BCRYPT, $options);
    return $password;
  }
  // Pass user Token to other functions
  public static function getUserToken() {

  }
}
