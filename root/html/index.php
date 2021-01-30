<?php

# REDIRECT TO HTTPS
if (!(
  $_SERVER['SERVER_PORT'] == '443'
  &&
  $_SERVER['HTTPS'] == 'on'
  &&
  (
    (isset($_SERVER['REQUEST_SCHEME'])) ? ($_SERVER['REQUEST_SCHEME'] == 'https') : true
  )
)) {
  header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
  exit;
}

# CREATE OR UPDATE HTACCESS
if ((isset($_GET['setfr']) && $_GET['setfr'] == 'here') || !file_exists('.htaccess')) {
  $_htaccess = fopen('.htaccess', 'w') or die('Unable to write htaccess.');
  fwrite($_htaccess, "RewriteEngine on\n\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^ index.php [L]");
  fclose($_htaccess);
  unset($_htaccess);
}

# ROOT ANCHOR FILE NAME
define('RAFN', 'root.dir');

# FIND ROOT DIRECTORY
// DEFINE BASEFILE AND PREPARE DIRECTORY ARRAY
$da = explode('/', ltrim(__FILE__, '/'));
define('BASEFILE', $da[count($da)-1]);
$_htmldir = '/' . $da[count($da)-2] . '/';
array_pop($da);
// LOOK FOR ROOT DIR
for ($i = count($da); $i > 0; $i--) {
  $dir = '/' . implode('/', $da) . '/';
  if (file_exists($dir . RAFN)) break;
  else array_pop($da);
}
define('ROOT', $dir);
unset($dir, $da);

# GET INDEX VARIABLES
if (file_exists(ROOT . 'index.ini')) {
  $index_ini = parse_ini_file(ROOT . 'index.ini', true);
  if (isset($index_ini[$_SERVER['HTTP_HOST']])) {
    $index = $index_ini[$_SERVER['HTTP_HOST']];
  } else {
    die('DOMAIN ' . $_SERVER['HTTP_HOST'] . ' NOT DEFINED IN index.ini');
  }
} else {
  die('index.ini NOT FOUND');
}
unset($index_ini);

# SET DEBUG MODE
define('DEBUG', $index['debug']);
ini_set('zlib.output_compression', $index['output_compression']);
if (DEBUG) {
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
}

# DEFINE DIRECTORIES
$_htmldir = ltrim($_htmldir, '/');
define("WWW", ROOT . $_htmldir);
unset($_htmldir);
define("MODULES", ROOT . $index['modules_dir'] . '/');
define("PHP", ROOT . $index['php_dir'] . '/');
define("STATIC", ROOT . $index['static_dir'] . '/');

# LOAD URLS
define(
  "BASEURL",
  'http' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . str_replace(BASEFILE, '', $_SERVER['SCRIPT_NAME'])
);
define("BASEURL_DIR", str_replace(BASEFILE, '', $_SERVER['SCRIPT_NAME']));

# AUTOLOAD CLASSES
spl_autoload_register(function ($class_name) {
  require PHP . strtolower($class_name) . '.class.php';
});

/* ALL CODE ABOVE IS NOT COVERED BY CUSTOM ERROR HANDLING - ENSURE NO RUNTIME ERRORS */

# CUSTOM ERROR HANDLING
function mlsErrorHandler(){}

# START APPLICATION
Gstatic::app(new Core());

# REGISTER SHUTDOWN FUNCTION - executed when exit() called

if (Gstatic::app()->getReqModName() == 'api') {
  define("SKIP_CONTENT", true);
  $_API = new Api();
  $_API->loadModule();
  unset($_API);
}

# START OUTPUT BUFFERING
ob_start();

if (!defined("SKIP_CONTENT")) {

  if (isset($index['icon'])) {
    Gstatic::app()->register_link_tag('icon', BASEURL . $index['icon']);
  }
  if (isset($index['preconnect'])) foreach ($index['preconnect'] as $pc) {
    Gstatic::app()->register_link_tag('preconnect',$pc);
  }
  if (isset($index['description'])) {
    Gstatic::app()->register_meta_tag('description',$index['description']);
  }
  if (isset($index['author'])) {
    Gstatic::app()->register_meta_tag('author',$index['author']);
  }
  if (isset($index['license'])) {
    Gstatic::app()->register_link_tag('license',$index['license']);
  }
  foreach ($index['reg_css'] as $rcss) {
    Gstatic::app()->register_css($rcss);
  }
  foreach ($index['reg_js'] as $js) {
    Gstatic::app()->register_js($js);
  }
  foreach ($index['reg_font'] as $rfont) {
    $rf = explode('|', $rfont);
    if (!isset($rf[3])) $rf[3] = false;
    Gstatic::app()->register_font($rf[0],$rf[1],$rf[2],(bool)$rf[3]);
  }
  $jq = explode('|', $index['jquery']);
  if (!isset($jq[2])) $jq[2] = false;
  Gstatic::app()->set_jquery($jq[0],$jq[1],(bool)$jq[2]);
  if (isset($index['bootstrap_js'])) {
    $bjs = explode('|', $index['bootstrap_js']);
    if (!isset($bjs[2])) $bjs[2] = false;
    Gstatic::app()->set_bootstrap_js($bjs[0],$bjs[1],(bool)$bjs[2]);
  }
  foreach ($index['reg_ext_css'] as $recss) {
    $rec = explode('|', $recss);
    if (!isset($rec[2])) $rec[2] = false;
    Gstatic::app()->register_ext_css($rec[0],$rec[1],(bool)$rec[2]);
  }

  require PHP . 'footer.inc.php';

  # PROCESS HTML AND CONTENT
  Gstatic::app()->compileHTML();

}

# COLLECT ALL OUTPUT
$output = ob_get_clean();

# START FINAL OUTPUT BUFFERING
ob_start();

# FINAL OUTPUT
echo str_replace('><', ">\n<", $output); // TODO: replace with postprocessing function

# END OUTPUT BUFFERING WITH FLUSH
ob_end_flush();

# CLOSE APPLICATION
Gstatic::quit();

exit;