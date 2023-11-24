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

      if ($this->pageNotFound($e) || $this->methodError($e)) {
        return new HttpFoundation\Response(
          $app['twig']->render('404.html.twig', ['message' => "{$e->getMessage()}:{$e->getCode()}"]),
          404
        );

      } elseif ($this->requireAuthentication($e)) {
        $app['flash']->addMessage($app['tr']->t('flashes.login_first'));
        return $app->redirect($app['url_generator']->generate('new_session', $redirect_variables));

      } elseif ($e instanceof SerwisantApi\ExceptionAccessDenied) {
        return new HttpFoundation\Response(
          $app['twig']->render('403.html.twig', [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getHandle(),
          ]),
          403
        );

      } elseif ($e instanceof SerwisantApi\ExceptionTooManyRequests) {
        return new HttpFoundation\Response($app['twig']->render('429.html.twig', []), 429);

      } else {
        throw $e;
      }
    };
  }

  private function methodError($e): bool
  {
    return ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException);
  }

  private function requireAuthentication($e): bool
  {
    return
      ($e instanceof ExceptionUnauthorized) ||
      ($e instanceof SerwisantApi\ExceptionUserCredentialsRequired);
  }

  private function pageNotFound($e): bool
  {
    return
      ($e instanceof ExceptionNotFound) ||
      ($e instanceof SerwisantApi\ExceptionNotFound) ||
      ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException);
  }
}