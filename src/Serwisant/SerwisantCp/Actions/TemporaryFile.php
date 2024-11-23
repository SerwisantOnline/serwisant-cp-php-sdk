<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\ExceptionNotFound;
use Symfony\Component\HttpFoundation;
use Serwisant\SerwisantApi\Types\SchemaCustomer\FileInput;
use Serwisant\SerwisantCp\Action;

class TemporaryFile extends Action
{
  public function create()
  {
    $this->checkModuleActive();

    $files = $this->request->files->get('temporary_files');

    if (!is_array($files) or count($files) != 1 or !($files[0] instanceof HttpFoundation\File\UploadedFile)) {
      return new HttpFoundation\Response('File is missing or multiple files sent', 422);
    }
    if (!$files[0]->isValid()) {
      return new HttpFoundation\Response('File error: ' . $files[0]->getErrorMessage(), 422);
    }

    $file = new FileInput([
      'contentType' => $files[0]->getClientMimeType(),
      'name' => $files[0]->getClientOriginalName(),
      'payload' => base64_encode(file_get_contents($files[0]->getPathname()))
    ]);

    $result = $this->apiCustomer()->customerMutation()->createTemporaryFile($file);

    if ($result->errors) {
      return new HttpFoundation\Response(print_r($result->errors, true), 422);
    } else {
      return new HttpFoundation\Response($result->temporaryFile->ID);
    }
  }

  public function show()
  {
    $this->checkModuleActive();

    $result = $this->apiCustomer()->customerQuery()->temporaryFiles([$this->request->get('load')]);

    if (is_array($result) && count($result) == 1) {
      $content = file_get_contents($result[0]->url);
      if ($content !== false) {
        $headers = [
          'Access-Control-Expose-Headers' => 'Content-Disposition, Content-Length, X-Content-Transfer-Id',
          'Content-Type' => $result[0]->contentType,
          'Content-Length' => strlen($content),
          'Content-Disposition' => 'inline; filename="' . $result[0]->name . '"',
          'X-Content-Transfer-Id' => $result[0]->ID,
        ];
        return new HttpFoundation\Response($content, 200, $headers);
      }
    }

    return new HttpFoundation\Response('', 404);
  }

  private function checkModuleActive()
  {
    if (!$this->getLayoutVars()['configuration']->uploadFiles) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
  }
}