<?php
namespace App\Definition;

class Controller
{
  protected $container;

  public function __construct($container)
  {
    $this->container = $container;
  }
}
