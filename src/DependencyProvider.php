<?php
  namespace elpho\di;

  interface DependencyProvider{
    function getProvidedClassName();
    function getInstance();
  }