<?php
libxml_use_internal_errors(true);   // for HTML5 compatibility
class Html {
  private $dom;
  private $html = [];
  private $metaTags = [];
  private $linkTags = [];
  private $otherHeadTags = [];
  private $bottomBodyTags = [];
  private $googleFonts = [];

  /**
   * --------------------------------------------------------------------------------
   *                              C O N S T R U C T O R
   * --------------------------------------------------------------------------------
   */
  public function __construct($baseFileName) {
    $base = file_get_contents(PHP . $baseFileName . '.html');
    $this->dom = new DOMDocument();    
    $this->dom->loadHTML(mb_convert_encoding($base, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();  // for HTML5 compatibility
    $this->html['head'] = $this->dom->getElementsByTagName('head')[0];
    $this->html['body'] = $this->dom->getElementsByTagName('body')[0];
  }

  /**
   * --------------------------------------------------------------------------------
   *                               D E S T R U C T O R
   * --------------------------------------------------------------------------------
   */
  public function __destruct() {
    $this->buildHEAD();
    $this->appendBodyTags();
    $final = $this->dom->saveHTML();
    echo $final;
    unset(
      $this->dom,
      $this->html,
      $this->metaTags,
      $this->linkTags,
      $this->otherHeadTags,
      $this->bottomBodyTags,
      $this->googleFonts
    );
  }

  /**
   * --------------------------------------------------------------------------------
   *                      M A I N   P U B L I C   M E T H O D S
   * --------------------------------------------------------------------------------
   */
  /**
   * Add tags at the end of BODY from queue
   */
  public function appendBodyTags() {
    foreach ($this->bottomBodyTags as $tag) {
      $this->html['body']->appendChild($tag);
    }
  }
  /**
   * Add tag to HEAD-insert queue
   * Accepts undefined number of arguments.
   * [0] - tag name
   * [1 (+2)] - attribute name
   * [2 (+2)] - attribute value
   */
  public function enqueueHeadTag() {
    $n = func_num_args();
    $a = func_get_args();
    $prepend = false;
    $tag = $this->dom->createElement($a[0]);
    $attr = null;
    for ($i = 1; $i < $n; $i++) {
      if ($i%2 == 1) {
        $attr = $this->dom->createAttribute($a[$i]);
      } else {
        if ($a[$i] !== null && $a[$i] !== false)
          $attr->value = $a[$i];
        $tag->appendChild($attr);
        $attr = null;
      }
    }
    if ($attr !== null) $tag->appendChild($attr);
    switch($a[0]) {
      case 'meta':
        array_push($this->metaTags, $tag);
        break;
      case 'link':
        array_push($this->linkTags, $tag);
        break;
      default:
        array_push($this->otherHeadTags, $tag);
    }
  }
  /**
   * Add tag to BODY-insert queue WITHOUT CHILD CONTENT
   * Accepts undefined number of arguments.
   * [0] - tag name
   * [1 (+2)] - attribute name
   * [2 (+2)] - attribute value
   */
  public function enqueueBodyEndTag() {
    $n = func_num_args();
    $a = func_get_args();
    $prepend = false;
    $tag = $this->dom->createElement($a[0]);
    $attr = null;
    for ($i = 1; $i < $n; $i++) {
      if ($i%2 == 1) {
        $attr = $this->dom->createAttribute($a[$i]);
      } else {
        if ($a[$i] !== null && $a[$i] !== false)
          $attr->value = $a[$i];
        $tag->appendChild($attr);
        $attr = null;
      }
    }
    array_push($this->bottomBodyTags, $tag);
  }
  /**
   * Add tag to BODY-insert queue WITH CHILD CONTENT
   * Accepts undefined number of arguments.
   * [0] - tag name
   * [1] - content (inside tag)
   * [2 (+2)] - attribute name
   * [3 (+2)] - attribute value
   */
  public function enqueueBodyEndTagWithContent() {
    $n = func_num_args();
    $a = func_get_args();
    $tag = $this->dom->createElement($a[0], $a[1]);
    $attr = null;
    for ($i = 2; $i < $n; $i++) {
      if ($i%2 == 1) {
        $attr = $this->dom->createAttribute($a[$i]);
      } else {
        if ($a[$i] !== null && $a[$i] !== false)
          $attr->value = $a[$i];
        $tag->appendChild($attr);
        $attr = null;
      }
    }
    array_push($this->bottomBodyTags, $tag);
  }
  public function putModuleContent($content) {
    $parent = $this->dom->getElementById('moduleContent');
    $temp = new DOMDocument();
    $temp->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();  // for HTML5 compatibility
    foreach ($temp->getElementsByTagName('body')->item(0)->childNodes as $node) {
      $node = $parent->ownerDocument->importNode($node, true);
      $parent->appendChild($node);
    }
  }
  public function loadMenu() {
    $parent = $this->dom->getElementById('sidebarMenu');
    $temp = new DOMDocument();
    $temp->loadHTML(mb_convert_encoding(file_get_contents(PHP . 'menu.html'), 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();  // for HTML5 compatibility
    foreach ($temp->getElementsByTagName('body')->item(0)->childNodes as $node) {
      $node = $parent->ownerDocument->importNode($node, true);
      $parent->appendChild($node);
    }
  }
  /**
   * Add Google font to load query
   * @var $weights - list weights one-by-one (1,2,3) OR as a range (1-3) [NO WHITESPACE] [COMBINED NOT ALLOWED]
   * @var $italics - if true, fonts listed in $weights will be also loaded as italics
   * NOTE: if italics are available, each weight is available in BOTH normal and italic style
   */
  public function addGoogleFont($name, $weights, $italics = false) {
    $f = 'family=' . str_replace(' ', '+', $name) . ':';
    $f .= ($italics) ? 'ital,wght' : 'wght';
    $w = [];
    if (strpos($weights, '-') !== false) {
      $weights = explode('-', $weights);
      for ($i = $weights[0]; $i <= $weights[1]; $i = $i + 100) { 
        if ($italics) {
          array_push($w, '0,' . $i);
          array_push($w, '1,' . $i);
        } else array_push($w, $i);
      }
      sort($w);
    } else {
      $weights = explode(',', $weights);
      foreach($weights as $wght) {
        if ($italics) {
          array_push($w, '0,' . $wght);
          array_push($w, '1,' . $wght);
        } else array_push($w, $wght);
      }
      sort($w);
    }
    $f .= '@' . implode(';', $w);
    array_push($this->googleFonts, $f);
  }

  /**
   * --------------------------------------------------------------------------------
   *                D E P R E C A T E D   P U B L I C   M E T H O D S
   * --------------------------------------------------------------------------------
   */
  /**
   * Add non-referable tags at the beginning of HEAD
   * (just before head open tag at time of execution)
   * ONLY ALLOWED --AFTER-- CONTENT MANIPULATION IS FINISHED
   * TODO: REMOVE -> DEPRECATED
   */ 
  public function addToHeadBegin($name, $attributes) {
    $this->prependElement($this->html['head'], $name, $attributes);
  }
  /**
   * Add non-referable tags at the end of HEAD
   * (just before head close tag at time of execution)
   * ONLY ALLOWED --AFTER-- CONTENT MANIPULATION IS FINISHED
   * TODO: REMOVE -> DEPRECATED
   */ 
  public function addToHeadEnd($name, $attributes) {
    $this->appendElement($this->html['head'], $name, $attributes);
  }
  /**
   * Add non-referable tags at the end of BODY
   * (just before body close tag at time of execution)
   * ONLY ALLOWED --AFTER-- CONTENT MANIPULATION IS FINISHED
   * TODO: REMOVE -> DEPRECATED
   */ 
  public function addToBodyEnd($name, $attributes = null, $content = null) {
    $this->appendElement($this->html['body'], $name, $attributes, $content);
  }
  /**
   * Import HTML code to end of BODY
   * (just before body close tag at time of execution)
   * TODO: MAKE PRIVATE with an intermediary method creating 'hook-points'
   * TODO: REFACTOR to UNIVERSAL append HTML function
   */
  public function appendHTMLtoBody($html) {
    $parent = $this->dom->getElementsByTagName('body')->item(0);
    $temp = new DOMDocument();
    $temp->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();  // for HTML5 compatibility
    foreach ($temp->getElementsByTagName('body')->item(0)->childNodes as $node) {
      $node = $parent->ownerDocument->importNode($node, true);
      $parent->appendChild($node);
    }
  }
  /**
   * Import HTML code to beginning of BODY
   * (just after body open tag at time of execution)
   * TODO: REMOVE -> DEPRECATED
   */
  public function prependHTMLtoBody($html) {
    $parent = $this->dom->getElementsByTagName('body')->item(0);
    $temp = new DOMDocument();
    $temp->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();  // for HTML5 compatibility
    foreach ($temp->getElementsByTagName('body')->item(0)->childNodes as $node) {
      $node = $parent->ownerDocument->importNode($node, true);
      $parent->insertBefore($node, $parent->firstChild);
    }
  }
  /**
   * TODO: COMBINE WITH `appendHTMLtoBody` function
   * TODO: REMOVE -> DEPRECATED
   */
  public function appendHTML($parentId, $html) {
    $parent = $this->dom->getElementById($parentId);
    if ($parent === null) {
      // log_error("Element with id #" . $parentId . " not found");
      return false;
    }
    $temp = new DOMDocument();
    $temp->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();  // for HTML5 compatibility
    foreach ($temp->getElementsByTagName('body')->item(0)->childNodes as $node) {
      $node = $parent->ownerDocument->importNode($node, true);
      $parent->appendChild($node);
    }
  }
  /**
   * TODO: COMBINE WITH `appendHTMLtoBody` function
   * TODO: REMOVE -> DEPRECATED
   */
  public function prependHTML($parentId, $html) {
    $parent = $this->dom->getElementById($parentId);
    if ($parent === null) {
      // log_error("Element with id #" . $parentId . " not found");
      return false;
    }
    $temp = new DOMDocument();
    $temp->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();  // for HTML5 compatibility
    foreach ($temp->getElementsByTagName('body')->item(0)->childNodes as $node) {
      $node = $parent->ownerDocument->importNode($node, true);
      $parent->insertBefore($node, $parent->firstChild);
    }
  }

  /**
   * --------------------------------------------------------------------------------
   *                     M A I N   P R I V A T E   M E T H O D S
   * --------------------------------------------------------------------------------
   */
  /**
   * Build final HEAD from queued tags
   */
  private function buildHEAD() {
    /* ---------------------------------------- buildHEAD() start */
    /// prepare Google Fonts for insert
    $this->linkGoogleFonts();

    /// insert TITLE tag
    $title = $this->dom->createElement('title', Gstatic::app()->getTitle());
    $this->html['head']->appendChild($title);

    /// insert BASE tag
    $base = $this->dom->createElement('base');
    $baseAttr = $this->dom->createAttribute('href');
    $baseAttr->value = BASEURL;
    $base->appendChild($baseAttr);
    $baseAttr = $this->dom->createAttribute('target');
    $baseAttr->value = '_top';
    $base->appendChild($baseAttr);
    $this->html['head']->insertBefore($base, $this->html['head']->firstChild);

    /// insert META tags
    foreach (array_reverse($this->metaTags) as $tag) {
      $this->html['head']->insertBefore($tag, $this->html['head']->firstChild);
    }

    /// insert LINK tags
    foreach ($this->linkTags as $tag) {
      $this->html['head']->appendChild($tag);
    }

    /// insert OTHER tags
    foreach ($this->otherHeadTags as $tag) {
      $this->html['head']->appendChild($tag);
    }
    /* ---------------------------------------- buildHEAD() end */
  }
  private function appendAttribute($parent, $name, $value = null) {
    $newAttr = $this->dom->createAttribute($name);
    if ($value !== null) $newAttr->value = $value;
    $parent->appendChild($newAttr);
  }
  private function appendElement($parent, $name, $attributes = null, $content = null) {
    if ($attributes === null || $attributes === false) $attributes = [];
    $newElement = $this->dom->createElement($name, $content);
    foreach ($attributes as $attr) {
      if (!isset($attr[1])) $attr[1] = null;
      $this->appendAttribute($newElement, $attr[0], $attr[1]);
    }
    $parent->appendChild($newElement);
  }
  private function prependElement($parent, $name, $attributes = null, $content = null) {
    if ($attributes === null || $attributes === false) $attributes = [];
    $newElement = $this->dom->createElement($name, $content);
    foreach ($attributes as $attr) {
      if (!isset($attr[1])) $attr[1] = null;
      $this->appendAttribute($newElement, $attr[0], $attr[1]);
    }
    $parent->insertBefore($newElement, $parent->firstChild);
  }
  private function linkGoogleFonts() {
    if (empty($this->googleFonts)) return;
    $href = 'https://fonts.googleapis.com/css2?';
    $href .= implode('&amp;', $this->googleFonts);
    $href .= '&amp;display=swap';
    $this->enqueueHeadTag('link', 'rel', 'stylesheet', 'href', $href);
  }
}
