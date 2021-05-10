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
      if ($this->is404exception($e)) {
        return new HttpFoundation\Response($app['twig']->render('404.html.twig', []), 404);
      } elseif ($this->is401exception($e)) {
        $app['flash']->addMessage($app['tr']->t($app['locale'], 'flashes.login_first'));
        return new HttpFoundation\RedirectResponse($app['url_generator']->generate('new_session'));
      } elseif ($this->isApiOauthException($e)) {
        return new HttpFoundation\RedirectResponse($app['url_generator']->generate('destroy_session'));
      } else {
        throw $e;
      }
    };
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

  private function isApiOauthException($e)
  {
    return ($e instanceof SerwisantApi\ExceptionUnauthorized);
  }
}