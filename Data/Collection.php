<?php
/*
 * Collection class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Data;

use \Exception;
use \Fight\Data\DAO;

class Collection
{
   public $objects = [];

   public function __construct($objects) {
      $this->objects = $objects;
   }

   public function save() {
      throw new Exception("Not implemented");
   }

   public function read() {
      $result = [];

      foreach($this->objects as $obj) {
         $result[] = $obj->read();
      }

      return $result;
   }

   public function size() {
      return count($this->objects);
   }

   public function at($index) {
      return $this->objects[$index];
   }

   public function first() {
      return $this->objects[0];
   }
}
