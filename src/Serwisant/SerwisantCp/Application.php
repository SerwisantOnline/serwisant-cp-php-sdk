<?php

namespace Serwisant\SerwisantCp;

use Silex;
use Symfony\Component\HttpFoundation;
use Serwisant\SerwisantApi;

class Application
{
  private $env;
  private $access_token_pubic;
  private $access_token_customer;
  private $router;
  private $base_dir;
  private $view_paths = [];
  private $query_paths = [];
  private $tr_files = [];

  public function __construct($env)
  {
    $this->env = $env;

    $this->base_dir = __DIR__;

    array_unshift($this->view_paths, $this->base_dir . '/views');
    array_unshift($this->query_paths, $this->base_dir . '/queries/customer');
    array_unshift($this->query_paths, $this->base_dir . '/queries/public');
    array_unshift($this->tr_files, $this->base_dir . '/translations/pl.yml');
  }

  public function setRouter(Router $router)
  {
    $this->router = $router;
    return $this;
  }

  public function setPublicAccessToken(SerwisantApi\AccessToken $token)
  {
    $this->access_token_pubic = $token;
    return $this;
  }

  public function setCustomerAccessToken(SerwisantApi\AccessToken $token)
  {
    $this->access_token_customer = $token;
    return $this;
  }

  public function run()
  {
    $app = new Silex\Application(['env' => $this->env, 'debug' => ($this->env === 'development')]);

    $app['env'] = $this->env;
    $app['base_dir'] = $this->base_dir;
    $app['gql_query_paths'] = $this->query_paths;
    $app['tr'] = new Translator($this->tr_files);
    $app['flash'] = new Flash();

    $app->register(new Silex\Provider\TwigServiceProvider(), ['twig.path' => $this->view_paths]);
    $app->register(new Silex\Provider\RoutingServiceProvider());
    $app->extend(
      'twig',
      function ($twig) use ($app) {
        return (new TwigGenericExtensions($twig, $app))->call();
      }
    );
    $app->extend(
      'twig',
      function ($twig) use ($app) {
        return (new TwigFormExtensions($twig, $app))->call();
      }
    );
    $app->extend(
      'twig',
      function ($twig) use ($app) {
        return (new TwigSerwisantExtensions($twig, $app))->call();
      }
    );
    $app->error((new ApplicationExceptionHandlers())->call($app));
    $app->before(function (HttpFoundation\Request $request, Silex\Application $app) {
      $this->beforeRequest($request, $app);
    });

    $this->router->createRoutes($app);

    $app->run();
  }

  private function beforeRequest(HttpFoundation\Request $request, Silex\Application $app)
  {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
      $data = json_decode($request->getContent(), true);
      $request->request->replace(is_array($data) ? $data : array());
    }

    $app['base_uri'] = $request->getScheme() . '://' . $request->getHost();
    $app['request'] = $request;
    $app['access_token_customer'] = $this->access_token_customer;
    $app['access_token_public'] = $this->access_token_pubic;

    # @fixme - tutaj wykonuję automatyczną detekcję z nagłówków żądania HTTP
    $app['locale'] = 'pl_PL';
    $app['timezone'] = 'Europe/Warsaw';

    setlocale(LC_ALL, $app['locale']);
    date_default_timezone_set($app['timezone']);
  }
}