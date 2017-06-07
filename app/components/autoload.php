<?php

function autoload($className)
{
  require(__DIR__ . '/' . str_replace('\\', '/', $className) . '.php');
}

spl_autoload_register('autoload');