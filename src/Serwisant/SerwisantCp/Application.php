<?php

namespace Serwisant\SerwisantCp;

use Silex;
use Symfony\Component\HttpFoundation;
use Serwisant\SerwisantApi;

class Application
{
  private $env;
  private $key;
  private $secret;
  private $base_dir;
  private $view_paths = [];
  private $query_paths = [];
  private $tr_files = [];

  public function __construct($env, $key, $secret)
  {
    $this->env = $env;
    $this->key = $key;
    $this->secret = $secret;

    $this->base_dir = __DIR__;

    array_unshift($this->view_paths, $this->base_dir . '/views');
    array_unshift($this->query_paths, $this->base_dir . '/queries/customer');
    array_unshift($this->query_paths, $this->base_dir . '/queries/public');
    array_unshift($this->tr_files, $this->base_dir . '/translations/pl.yml');
  }

  /**
   * @return Router
   */
  protected function getRouter()
  {
    return new RouterSelfHosted();
  }

  protected function accessTokenUrl()
  {
    $url = null;
    if ($this->env == 'development') {
      $url = 'http://127.0.0.1:3000/oauth/token';
    }
    return $url;
  }

  protected function getPublicAccessToken()
  {
    return new SerwisantApi\AccessTokenOauth(
      $this->key,
      $this->secret,
      'public',
      new SerwisantApi\AccessTokenContainerFile(),
      $this->accessTokenUrl()
    );
  }

  protected function getCustomerAccessToken()
  {
    return new SerwisantApi\AccessTokenOauthUserCredentials(
      $this->key,
      $this->secret,
      'customer',
      new SerwisantApi\AccessTokenContainerSession(),
      $this->accessTokenUrl()
    );
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
      if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
      }

      $app['base_uri'] = $request->getScheme() . '://' . $request->getHost();
      $app['access_token_customer'] = $this->getCustomerAccessToken();
      $app['access_token_public'] = $this->getPublicAccessToken();
      $app['locale'] = 'pl_PL';
      $app['timezone'] = 'Europe/Warsaw';

      setlocale(LC_ALL, $app['locale']);
      date_default_timezone_set($app['timezone']);
    });

    $this->getRouter()->createRoutes($app);
    $app->run();
  }
}