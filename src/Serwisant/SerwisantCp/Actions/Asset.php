<?php

namespace Serwisant\SerwisantCp\Actions;

use Symfony\Component\HttpFoundation;
use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

class Asset extends Action
{
  public function call($filename, $extension)
  {
    $assets_dir = "{$this->app['base_dir']}/assets";
    $allowed_extensions = [
      'css' => '	text/css',
      'js' => 'text/javascript; charset=UTF-8',
      'png' => 'image/png',
      'gif' => 'image/gif',
      'jpg' => 'image/jpeg'
    ];

    if (strlen(trim($filename)) > 0 && true === in_array($extension, array_keys($allowed_extensions))) {
      $file_path = "{$assets_dir}/{$filename}.{$extension}";
      if ($this->debug) {
        error_log("Serving asset {$file_path}");
      }
      if (true === is_readable($file_path)) {
        $handle = fopen($file_path, "r");
        $contents = fread($handle, filesize($file_path));
        fclose($handle);

        $response = new HttpFoundation\Response($contents, 200);
        $response->headers->set('Content-Type', $allowed_extensions[$extension]);
        $response->headers->set('Content-Disposition', 'inline');
        $response->headers->set('Cache-Control', 'max-age=86400, must-revalidate');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s ', filectime($file_path)) . 'GMT');
        $response->headers->set('ETag', sha1(filectime($file_path)));
        return $response;
      }
    }

    throw new ExceptionNotFound();
  }
}