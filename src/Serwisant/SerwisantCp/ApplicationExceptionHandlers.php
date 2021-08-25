<?php

namespace Serwisant\SerwisantCp;

use Silex;
use Symfony\Component\HttpFoundation;
use Serwisant\SerwisantApi;

class ApplicationExceptionHandlers
{
  public function call(Silex\Application $app)
  {
    return function (\Exception $e) use ($app) {
      $redirect_variables = [];
      if (isset($app['token'])) {
        $redirect_variables['token'] = (string)$app['token'];
      }

      if ($this->pageNotFound($e)) {
        return new HttpFoundation\Response(
          $app['twig']->render('404.html.twig', ['message' => "{$e->getMessage()}:{$e->getCode()}"]),
          404
        );

      } elseif ($this->requireAuthentication($e)) {
        $app['flash']->addMessage($app['tr']->t($app['locale'], 'flashes.login_first'));
        return $app->redirect($app['url_generator']->generate('new_session', $redirect_variables));

      } elseif ($e instanceof SerwisantApi\ExceptionAccessDenied) {
        return new HttpFoundation\Response(
          $app['twig']->render('403.html.twig', [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getHandle(),
          ]),
          403
        );

      } else {
        throw $e;
      }
    };
  }

  /**
   * @param $e
   * @return bool
   */
  private function requireAuthentication($e)
  {
    return
      ($e instanceof ExceptionUnauthorized) ||
      ($e instanceof SerwisantApi\ExceptionUserCredentialsRequired);
  }

  /**
   * @param $e
   * @return bool
   */
  private function pageNotFound($e)
  {
    return
      ($e instanceof ExceptionNotFound) ||
      ($e instanceof SerwisantApi\ExceptionNotFound) ||
      ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException);
  }
}