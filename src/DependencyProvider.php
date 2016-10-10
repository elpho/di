<?php
  namespace elpho\di;

  interface DependencyProvider{
    static function getProvidedClassName();
    static function getInstance();
  }