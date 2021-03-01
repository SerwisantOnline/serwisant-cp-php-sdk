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
    return new RouterCp();
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
    $app['locale'] = 'pl_PL';
    $app['timezone'] = 'Europe/Warsaw';
    $app['gql_query_paths'] = $this->query_paths;
    $app['tr'] = new Translator($this->tr_files);

    $app->register(new Silex\Provider\TwigServiceProvider(), ['twig.path' => $this->view_paths]);
    $app->register(new Silex\Provider\RoutingServiceProvider());

    $app->extend(
      'twig',
      function ($twig) use ($app) {
        return (new TwigExtensions($twig, $app))->call();
      }
    );

    $app->error(function (\Exception $e) use ($app) {
      if ($this->is404exception($e)) {
        return new HttpFoundation\Response($app['twig']->render('404.html.twig', []), 404);
      } elseif ($this->is401exception($e)) {
        return new HttpFoundation\RedirectResponse($app['url_generator']->generate('new_session'));
      } else {
        throw $e;
      }
    });

    $app->before(function (HttpFoundation\Request $request, Silex\Application $app) {
      if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
      }

      $app['base_uri'] = $request->getScheme() . '://' . $request->getHost();

      $app['access_token_customer'] = $this->getCustomerAccessToken();
      $app['access_token_public'] = $this->getPublicAccessToken();

      setlocale(LC_ALL, $app['locale']);
      date_default_timezone_set($app['timezone']);
    });

    $this->getRouter()->createRoutes($app);

    $app->run();
  }

  /**
   * @param $e
   * @return bool
   */
  private function is401exception($e)
  {
    return ($e instanceof SerwisantApi\ExceptionUserCredentialsRequired);
  }

  /**
   * @param $e
   * @return bool
   */
  private function is404exception($e)
  {
    return
      ($e instanceof ExceptionNotFound) ||
      ($e instanceof SerwisantApi\ExceptionNotFound) ||
      ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException);
  }
}