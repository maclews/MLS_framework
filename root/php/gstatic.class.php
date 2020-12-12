<?php
class Gstatic {
  private static $_app_handle = null;
  private static $_stopWatch = [];
  private static $_debugMode = false;
  private static $_MSUB = [];
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
}
