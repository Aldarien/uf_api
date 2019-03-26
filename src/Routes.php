<?php
namespace UF\API;

class Routes
{
  protected $app;
  protected $base;

  public function __construct(&$app, $base = '') {
    $this->app = $app;
    $this->base = $base;
  }
  public function register()
  {
    $app = $this->app;
    if ($this->base != '') {
      $app->group($this->base, function($app) {
        $this->routes($app);
      });
      return $app;
    }
    $this->routes($app);
  }
  protected function routes(&$app) {
    $app->group('/uf', function($app) {
      $app->get('/help', UFApi::class . ':help');
      $app->get('/value[/{arg}[/{month}[/{day}]]]', UFApi::class . ':value');
      $app->get('/list[/{year}[/{month}]]', UFApi::class . ':ufs');
      $app->get('/transform/{to}/{value}[/{arg}[/{month}[/{day}]]]', UFApi::class . ':transform');
      $app->get('/setup', UFApi::class . ':setup');
      $app->get('/load[/{year}[/{getter}]]', UFApi::class . ':load');
      $app->get('/remove[/{arg}[/{month}[/{day}]]]', UFApi::class . ':remove');
      $app->get('/delete[/{arg}[/{month}[/{day}]]]', UFApi::class . ':remove');
      $app->get('/{cmd}[/{arg}[/{month}[/{day}]]]', UFApi::class . ':index');
    });
  }
}
