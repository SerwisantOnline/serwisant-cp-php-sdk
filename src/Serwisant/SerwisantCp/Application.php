<?php

namespace Serwisant\SerwisantCp;

use Silex;
use Symfony\Component\HttpFoundation;
use Serwisant\SerwisantApi;

class Application
{
  private $env;
  private $view_paths = [];
  private $query_paths = [];
  private $tr_files = [];

  private $base_dir;

  private $app;
  private $router;

  public function __construct($env = 'production', $view_paths = [], $query_paths = [], $tr_files = [])
  {
    $this->env = $env;
    $this->view_paths = $view_paths;
    $this->query_paths = $query_paths;
    $this->tr_files = $tr_files;

    $this->base_dir = __DIR__;

    // widoki i zapytania dokładane są na koniec, własne pliki mogą nadpisywać te domyślne
    $this->view_paths[] = $this->base_dir . '/views';

    $this->query_paths[] = $this->base_dir . '/queries/customer';
    $this->query_paths[] = $this->base_dir . '/queries/public';

    array_unshift($this->tr_files, $this->base_dir . '/translations/pl.yml');

    # wszystkie czasy liczę względem UTC
    date_default_timezone_set('UTC');

    $this->app = new Silex\Application(['env' => $this->env, 'debug' => ($this->env === 'development')]);
  }

  public function setRouter(Router $router)
  {
    $this->router = $router;
    return $this;
  }

  public function set($key, $obj)
  {
    $this->app[$key] = $obj;
    return $this;
  }

  public function run()
  {
    $this->sessionStart();

    $this->app['env'] = $this->env;
    $this->app['base_dir'] = $this->base_dir;
    $this->app['gql_query_paths'] = $this->query_paths;
    $this->app['tr'] = new Translator($this->tr_files);
    $this->app['flash'] = new Flash();

    $this->app->register(new Silex\Provider\TwigServiceProvider(), ['twig.path' => $this->view_paths]);
    $this->app->register(new Silex\Provider\RoutingServiceProvider());

    $this->app->extend(
      'twig',
      function ($twig) {
        return (new TwigGenericExtensions($twig, $this->app))->call();
      }
    );
    $this->app->extend(
      'twig',
      function ($twig) {
        return (new TwigFormExtensions($twig, $this->app))->call();
      }
    );
    $this->app->extend(
      'twig',
      function ($twig) {
        return (new TwigSerwisantExtensions($twig, $this->app))->call();
      }
    );
    if (isset($this->app['action_decorator'])) {
      $decorator_twig_extension = $this->app['action_decorator']->getTwigExtension($this->app);
      if (is_callable($decorator_twig_extension)) {
        $this->app->extend('twig', $decorator_twig_extension);
      }
    }

    $this->app->error((new ApplicationExceptionHandlers())->call($this->app));

    $this->app->before(function (HttpFoundation\Request $request, Silex\Application $app) {
      $this->beforeRequest($request, $app);
    });

    $this->router->createRoutes($this->app);

    $this->app->run();

    return $this;
  }

  protected function sessionStart()
  {
    $session_optipns = ['cookie_lifetime' => (60 * 60 * 6)];

    if (isset($this->app['session_handler'])) {
      session_set_save_handler($this->app['session_handler'], true);
    } else {
      $dir = getenv('TMPDIR');
      if (is_null($dir) || trim($dir) == '') {
        $dir = sys_get_temp_dir();
      }
      if (!is_dir($dir)) {
        throw new Exception("Directory do not exists - please create '{$dir}' directory.");
      }
      $session_optipns['save_path'] = getenv('TMPDIR');
    }

    session_start($session_optipns);
  }

  protected function beforeRequest(HttpFoundation\Request $request, Silex\Application $app)
  {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
      $data = json_decode($request->getContent(), true);
      $request->request->replace(is_array($data) ? $data : array());
    }

    $app['base_uri'] = $request->getScheme() . '://' . $request->getHost();
    $app['request'] = $request;

    # ustawiam locale zgodne z językiem klienta, nie zmieniam natomiast domyślnej strefy czasowej - daty przed wyświetleniem
    # muszą zostać sformatowane zgodnie z $app[timezone]

    $app['locale'] = 'pl_PL';
    setlocale(LC_ALL, $app['locale']);

    $app['timezone'] = 'Europe/Warsaw';
  }
}