<?php
class Api {
  private $module;
  private $output;
  private $pretty_print = false;
  function __construct() {
    if (isset($_GET['pp']) && $_GET['pp'] == true) $this->pretty_print = true;
    $this->module = Gstatic::app()->getRequestedFunctionName(1);
  }
  function __destruct() {
    if (!empty($this->output)) {
      header('Content-type: application/json; charset=utf-8');
      echo (($this->pretty_print) ? json_encode($this->output, JSON_PRETTY_PRINT) : json_encode($this->output));
    }
  }
  public function loadModule() {
    $result;
    require MODULES . 'api/' . $this->module . '.php';
    $this->output = $result;
  }
}