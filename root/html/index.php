<?php

# CREATE OR UPDATE HTACCESS
if ((isset($_GET['setfr']) && $_GET['setfr'] == 'here') || !file_exists('.htaccess')) {
  $_htaccess = fopen('.htaccess', 'w') or die('Unable to write htaccess.');
  fwrite($_htaccess, "RewriteEngine on\n\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^ index.php [L]");
  fclose($_htaccess);
  unset($_htaccess);
}

# SET DEBUG MODE
define('DEBUG', true);
ini_set('zlib.output_compression', 'On');
if (DEBUG) {
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
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
$_htmldir = ltrim($_htmldir, '/');

# DEFINE DIRECTORIES
define("WWW", ROOT . $_htmldir);
unset($_htmldir);
define("MODULES", ROOT . 'modules/');
define("PHP", ROOT . 'php/');
define("STATIC", ROOT . 'static/');

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

  Gstatic::app()->register_css('sos.min.css');
  Gstatic::app()->register_font('Google', 'IBM Plex Sans', '300, 400, 500, 700', true);
  Gstatic::app()->register_font('Google', 'IBM Plex Serif', '300, 400, 500, 700', true);
  Gstatic::app()->register_font('Google', 'IBM Plex Mono', '300, 400, 500, 700', true);
  Gstatic::app()->register_font('Google', 'IBM Plex Sans Condensed', '300, 400, 500, 700', true);
  Gstatic::app()->set_jquery([
    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js',
    'integrity' => 'sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==',
    'crossorigin' => 'anonymous'
  ]);
  Gstatic::app()->set_bootstrap_js([
    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.3/js/bootstrap.bundle.min.js',
    'integrity' => 'sha512-iceXjjbmB2rwoX93Ka6HAHP+B76IY1z0o3h+N1PeDtRSsyeetU3/0QKJqGyPJcX63zysNehggFwMC/bi7dvMig==',
    'crossorigin' => 'anonymous'
  ]);
  Gstatic::app()->register_ext_css(
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css',
    'sha512-+4zCK9k+qNFUR5X+cKL9EIR+ZOhtIloNl9GIKS57V1MyNsYpYcUrUeQc9vNfzsWfV28IaLL3i96P9sdNyeRssA=='
  );

  # PROCESS HTML AND CONTENT
  Gstatic::app()->compileHTML('sos');

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