<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantApi\Types\SchemaPublic\FileInput;

class TemporaryFilePublic extends TemporaryFile
{

  protected function temporaryFilesMutation()
  {
    return $this->apiPublic()->publicMutation();
  }

  protected function temporaryFilesQuery()
  {
    return $this->apiPublic()->publicQuery();
  }

  protected function fileInput($content_type, $name, $payload)
  {
    return new FileInput(['contentType' => $content_type, 'name' => $name, 'payload' => $payload]);
  }
}