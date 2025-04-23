<?php

namespace Serwisant\SerwisantCp\Traits;

use Serwisant\SerwisantApi\Types\SchemaCustomer;
use Serwisant\SerwisantApi\Types\SchemaPublic;
use Serwisant\SerwisantCp\ExceptionNotFound;

trait Devices
{
  /**
   * @return SchemaCustomer\Device
   * @throws ExceptionNotFound
   */
  private function getDevice(): ?SchemaCustomer\Device
  {
    if ($device_id = $this->request->get('device')) {
      $devices_filter = new SchemaCustomer\DevicesFilter(['type' => SchemaCustomer\DevicesFilterType::ID, 'ID' => $device_id]);
      $devices = $this->apiCustomer()->customerQuery()->devices(1, null, $devices_filter, null, ['single' => true]);
      if (count($devices->items) == 0) {
        throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
      $device = $devices->items[0];
      if (false === $device->isVerified) {
        throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
      return $device;
    }
    return null;
  }

  /**
   * @return SchemaPublic\Device|null
   * @throws ExceptionNotFound
   */
  private function getDevicePublic(): ?SchemaPublic\Device
  {
    if ($device_id = $this->request->get('device')) {
      $device = $this->apiPublic()->publicQuery()->device($device_id);
      if (false === $device->isVerified) {
        throw new ExceptionNotFound(__CLASS__, __LINE__);
      }
      return $device;
    }
    return null;
  }
}
