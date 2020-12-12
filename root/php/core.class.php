<?php
class Core {
  private $db_handle;
  private $requestURI;
  private $settings;
  private $modTitle;
  private $appTitle;
  private $htmlOn;
  private $HTML_CSS;
  private $HTML_EXT_CSS;
  private $HTML_FONTS;
  private $HTML_JQUERY;
  private $HTML_BOOTSTRAP_JS;
  private $HTML_JS;

  /**
   * --------------------------------------------------------------------------------
   *                          P R I V A T E   M E T H O D S
   * --------------------------------------------------------------------------------
   */
  private function setUrlBase() {
    if (!isset($_SERVER['CONTEXT_PREFIX'])) {
      $scriptname = explode("/", $_SERVER['SCRIPT_NAME']);
      $_SERVER['CONTEXT_PREFIX'] = str_replace($scriptname[count($scriptname)-1], "", $_SERVER['SCRIPT_NAME']);
    }
    $_SERVER['URL_BASE'] = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['CONTEXT_PREFIX'];
  }

  private function prepareURI() {
    $req = explode('#', explode('?', $_SERVER['REQUEST_URI'])[0])[0];
    $raw = (BASEURL_DIR != '/') ? str_replace(BASEURL_DIR, '', $req) : $req;
    $this->requestURI = explode('/', trim($raw, '/'));
  }

  private function setURL() {
    $url_ini = parse_ini_file(ROOT . 'url.ini', true);
    if (isset($url_ini[$_SERVER['HTTP_HOST']])) {
      $urls = $url_ini[$_SERVER['HTTP_HOST']];
      define("STATIC_URL", $urls['static']);
    } else throw new Exception('Undefined URLs - check url.ini');
  }

  private function prepareSettings() {
    $this->settings = parse_ini_file(ROOT . 'cfg.ini');
  }

  private function prepareTitle() {
    $this->modTitle = null;
    $this->appTitle = $this->settings['title'];
  }

  /**
   * --------------------------------------------------------------------------------
   *                           P U B L I C   M E T H O D S
   * --------------------------------------------------------------------------------
   */
  public function getReqModName() : string {
    if (isset($this->requestURI[0]) && $this->requestURI[0] != '') {
      return $this->requestURI[0];
    } else return '';
  }

  public function postprocess(&$html, $ini_file) {
    $ini = parse_ini_file($ini_file, true);
    $search = [];
    $replace = [];
    foreach($ini as $i) {
      array_push($search, $i['search']);
      array_push($replace, $i['replace']);
    }
    $html = str_replace($search, $replace, $html);
  }
  
  public function getRequestedFunctionName($level) : string {
    $level = ($level < 1) ? 1 : (int)$level;
    if (isset($this->requestURI[$level]) && $this->requestURI[$level] != '')
      return $this->requestURI[$level];
    else return '';
  }

  public function includeSubModule($name) {
    $mod = $this->getReqModName();
    if ($mod == '') throw new Exception('Base Module is undefined');
    $subMod = MODULES . $mod . '/' . $name . '.php';
    if (file_exists($subMod)) require $subMod;
    else throw new Exception('Submodule not found.');
  }

  /// Pass handle to Database
  public function db() {
    return $this->db_handle;
  }

  public function setModuleTitle($title) {
    $this->modTitle = $title;
  }

  public function register_css($name) {
    if (array_search($name, $this->HTML_CSS) === false) array_push($this->HTML_CSS, $name);
    // TODO: add to error log 'CSS already registered'
  }

  public function register_ext_css($href, $integrity, $crossorigin = 'anonymous') {
    array_push($this->HTML_EXT_CSS, [
      'href' => $href,
      'integrity' => $integrity,
      'crossorigin' => $crossorigin
    ]);
  }

  public function register_font() {
    $n = func_num_args();
    $a = func_get_args();
    if (strtolower($a[0]) == 'google') {
      if ($n < 4) $a[3] = false;
      $temp = [
        'name' => $a[1],
        'weights' => str_replace(' ', '', $a[2]),
        'italics' => $a[3]
      ];
      array_push($this->HTML_FONTS, $temp);
    }
  }

  public function set_jquery($array) {
    $this->HTML_JQUERY = $array;
  }

  public function set_bootstrap_js($array) {
    $this->HTML_BOOTSTRAP_JS = $array;
  }

  public function register_js($name) {
    if (array_search($name, $this->HTML_JS) === false) array_push($this->HTML_JS, $name);
    // TODO: add to error log 'JS already registered'
  }

  public function compileHTML($baseFileName) {
    if ($this->htmlOn) throw new Exception('HTML compiler already running');  // prevent another execution
    else $this->htmlOn = true;

    $_HTML = new Html($baseFileName);

    $_HTML->enqueueHeadTag('meta','charset','utf-8');
    $_HTML->enqueueHeadTag('meta','name','viewport','content','width=device-width, initial-scale=1, shrink-to-fit=no');

    $_module = $this->getReqModName();
    if ($_module != '') {
      if (file_exists(MODULES . $_module . '.php')) {
        ob_start();
        require MODULES . $_module . '.php';
        $content = ob_get_clean();
        $_HTML->putModuleContent($content);
      } else {
        echo "Module `$_module` not found.";
      }
    } else {
      if (file_exists(MODULES . 'home.php')) {
        ob_start();
        require MODULES . 'home.php';
        $content = ob_get_clean();
        $_HTML->putModuleContent($content);
      }
    }
    
    foreach ($this->HTML_CSS as $css) $_HTML->enqueueHeadTag('link','rel','stylesheet','href',STATIC_URL.'css/'.$css);
    foreach ($this->HTML_EXT_CSS as $css) $_HTML->enqueueHeadTag('link','rel','stylesheet','href',$css['href'],'integrity',$css['integrity'],'crossorigin',$css['crossorigin']); 
    foreach ($this->HTML_FONTS as $font) $_HTML->addGoogleFont($font['name'],$font['weights'],$font['italics']);
    $_HTML->enqueueBodyEndTag('script','src',$this->HTML_JQUERY['src'],'integrity',$this->HTML_JQUERY['integrity'],'crossorigin',$this->HTML_JQUERY['crossorigin']);
    $_HTML->enqueueBodyEndTag('script','src',$this->HTML_BOOTSTRAP_JS['src'],'integrity',$this->HTML_BOOTSTRAP_JS['integrity'],'crossorigin',$this->HTML_BOOTSTRAP_JS['crossorigin']);
    foreach ($this->HTML_JS as $js) $_HTML->enqueueBodyEndTag('script','async','async','src',STATIC_URL.'js/'.$js);

    $_HTML->loadMenu();

    unset($_HTML);
  }

  public function getTitle() : string {
    $title = $this->appTitle;
    if ($this->modTitle != null) $title = $this->modTitle . " &bull; " . $title;
    return $title;
  }

  /**
   * --------------------------------------------------------------------------------
   *                              C O N S T R U C T O R
   * --------------------------------------------------------------------------------
   */
  function __construct() {
    $this->prepareSettings();
    $this->setURL();
    $this->prepareURI();
    $this->prepareTitle();
    $this->htmlOn = false;
    $this->HTML_CSS = [];
    $this->HTML_EXT_CSS = [];
    $this->HTML_FONTS = [];
    $this->HTML_JS = [];
    $this->db_handle = new Db();
  }

  /**
   * --------------------------------------------------------------------------------
   *                               D E S T R U C T O R
   * --------------------------------------------------------------------------------
   */
  function __destruct() {
    unset(
      $this->db_handle,
      $this->requestURI,
      $this->settings,
      $this->modTitle,
      $this->appTitle,
      $this->htmlOn,
      $this->HTML_CSS,
      $this->HTML_EXT_CSS,
      $this->HTML_FONTS,
      $this->HTML_JQUERY,
      $this->HTML_BOOTSTRAP_JS,
      $this->HTML_JS
    );
  }
}
