<?php namespace ProcessWire;
// info snippet
class RockHookSnippets extends WireData implements Module, ConfigurableModule {
  private $path;
  private $file;

  public static function getModuleInfo() {
    return [
      'title' => 'RockHookSnippets',
      'version' => '0.0.2',
      'summary' => 'ProcessWire Hook Snippets Generator for VSCode',
      'autoload' => true,
      'singular' => true,
      'icon' => 'anchor',
      'requires' => ['TracyDebugger>=4.20.17'],
      'installs' => [],
    ];
  }

  public function init() {
    $this->path = $this->config->paths->root . ".vscode/";
    $this->file = $this->path . "php.code-snippets";
    $rhs = $this;

    // create snippets file
    $config = $this->wire('config'); /** @var Config $config */
    if(in_array($config->httpHost, $this->getHosts())) {
      $this->files->mkdir($this->path);
      if(!is_file($this->file)) $this->createSnippetfile();
      else {
        $this->addHookAfter("Modules::refresh", function(HookEvent $event) use($rhs) {
          $rhs->createSnippetfile();
          $this->message('RockHookSnippets refreshed');
        });
      }
    }
  }

  /**
   * Create hooks snippet file
   * @return void
   */
  public function createSnippetfile() {
    // if(is_file($this->file)) return;
    $hooks = \TracyDebugger::getApiData('hooks');
    $content = [];
    foreach($hooks as $hook) {
      $hook = (object)$hook;
      $class = $hook->classname;
      foreach($hook->pwFunctions as $func) {
        $func = (object)$func;
        if(!property_exists($func, 'params')) $func->params = [];
        $description = property_exists($func, 'description')
          ? $func->description
          : '';

        // inline hook
        $body = [];
        $body[] = '\\$this->addHook${1:After}("'.$func->name.'", function(HookEvent \\$event) {';
        $body[] = '  \\$'.strtolower($class).' = \\$event->object; /** @var '.$class.' \\$'.strtolower($class).' */';
        foreach($func->params as $var => $type) {
          $body[] = '  \\'.$var.' = \\$event->arguments("'.substr($var, 1).'"); /** @var '.$type.' \\'.$var.' */';
        }
        $body[] = '  $0';
        $body[] = '});';

        $content[$func->name." inline"] = (object)[
          "prefix" => "i_".$func->name,
          "body" => $body,
          "description" => $description,
        ];

        // regular hook
        $body = [];
        $body[] = '\\$this->addHook${1:After}("'.$func->name.'", \\$this, "${2:yourMethodName}");';
        $body[] = 'public function $2(HookEvent \\$event) {';
        $body[] = '  \\$obj = \\$event->object; /** @var '.$class.' \\$obj */';
        foreach($func->params as $var => $type) {
          $body[] = '  \\'.$var.' = \\$event->arguments("'.substr($var, 1).'"); /** @var '.$type.' \\'.$var.' */';
        }
        $body[] = '  $0';
        $body[] = '}';
        
        $content[$func->name] = (object)[
          "prefix" => "__".$func->name,
          "body" => $body,
          "description" => $description,
        ];
      }
    }
    $content = (object)$content;
    $this->files->filePutContents($this->file, json_encode($content));
  }

  /**
   * Get hosts from settings
   */
  public function getHosts() {
    $hosts = [];
    foreach(explode(",", $this->hosts) as $host) $hosts[] = trim($host, " ");
    return $hosts;
  }

  /**
  * Config inputfields
  * @param InputfieldWrapper $inputfields
  */
  public function getModuleConfigInputfields($inputfields) {
    $current = "current is ".$this->config->httpHost;

    /** @var InputfieldText $f */
    $f = $this->wire('modules')->get('InputfieldText');
    $f->name = 'hosts';
    $f->label = 'Hosts';
    $f->description = "List all hosts where the snippets file should get created (non-production hosts)";
    $f->notes = "Separate hosts with commas, eg: 'www.foo.test, www.bar.test'\n$current";
    $f->value = $this->hosts;
    $inputfields->add($f);

    return $inputfields;
  }
}
