<?php

namespace Serwisant\SerwisantCp\Traits;

use Serwisant\SerwisantApi\Types\SchemaCustomer\Device;
use Serwisant\SerwisantApi\Types\SchemaCustomer\DevicesFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\DevicesFilterType;
use Serwisant\SerwisantCp\ExceptionNotFound;

trait Devices
{

  /**
   * @return Device
   */
  private function getDevice(): ?Device
  {
    if ($device_id = $this->request->get('device')) {
      $devices_filter = new DevicesFilter(['type' => DevicesFilterType::ID, 'ID' => $device_id]);
      $devices = $this->apiCustomer()->customerQuery()->devices(1, null, $devices_filter, null, ['single' => true]);
      $device = $devices->items[0];
      if (false === $device->isVerified) {
        throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
      return $device;
    }
    return null;
  }
}
