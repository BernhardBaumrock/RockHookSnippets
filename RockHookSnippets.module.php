<?php namespace ProcessWire;
// info snippet
class RockHookSnippets extends WireData implements Module {
  private $path;

  public static function getModuleInfo() {
    return [
      'title' => 'RockHookSnippets',
      'version' => '0.0.1',
      'summary' => 'ProcessWire Hook Snippets Generator for VSCode',
      'autoload' => true,
      'singular' => true,
      'icon' => 'anchor',
      'requires' => ['TracyDebugger'],
      'installs' => [],
    ];
  }

  public function init() {
    $this->path = $this->config->paths->root . ".vscode/";
    $this->files->mkdir($this->path);
  }

  /**
   * Create hooks snippet file
   * @return void
   */
  public function createSnippetfile() {
    $file = $this->path . "php.code-snippets";
    $hooks = \TracyDebugger::getApiData('hooks');
    $content = [];
    foreach($hooks as $hook) {
      $hook = (object)$hook;
      $class = $hook->classname;
      foreach($hook->pwFunctions as $func) {
        $func = (object)$func;

        // inline hook
        $body = [];
        $body[] = '\\$this->addHook${1:After}("'.$func->name.'", function(HookEvent \\$event) {';
        $body[] = '  \\$'.strtolower($class).' = \\$event->object; /** @var '.$class.' \\$'.strtolower($class).' */';
        $body[] = '  $0';
        $body[] = '});';
        $content[$func->name." inline"] = (object)[
          "prefix" => "i_".$func->name,
          "body" => $body,
        ];

        // regular hook
        $body = [];
        $body[] = '\\$this->addHook${1:After}("'.$func->name.'", \\$this, "${2:yourMethodName}");';
        $body[] = 'public function $2(HookEvent \\$event) {';
        $body[] = '  \\$'.strtolower($class).' = \\$event->object; /** @var '.$class.' \\$'.strtolower($class).' */';
        $body[] = '  $0';
        $body[] = '}';
        $content[$func->name] = (object)[
          "prefix" => "__".$func->name,
          "body" => $body,
        ];
      }
    }
    $content = (object)$content;
    $this->files->filePutContents($file, json_encode($content));
  }
}
