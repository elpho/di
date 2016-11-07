<?php
  namespace elpho\di;

  use elpho\lang\ProtoObject;

  class DependencyInjector{
    private $providers = null;

    public function __construct(){
      $this->providers = new ProtoObject();
    }

    public function registerProvider($provider){
      $providerName = null;

      if(is_string($provider))
        $providerName = $provider;

      if(is_object($provider))
        $providerName = get_class($provider);

      if(!class_exists($providerName))
        throw new \Exception("Invalid dependency provider: '".$providerName."' not found");

      if(!in_array('elpho\\di\\DependencyProvider', class_implements($providerName)))
        throw new \Exception("Invalid dependency provider: '".$providerName."' does not implement DependencyProvider interface");

      $className = call(array($provider, 'getProvidedClassName'));

      $this->providers->{$className} = $provider;
    }

    public function inject($target, $normalArgArray=null){
      $instance = null;

      if(is_string($target) && class_exists($target))
        $target = new \ReflectionClass($target);

      if(is_array($target) && is_callable($target)){
        if(!is_string($target[0]))
          $instance = $target[0];
        $target = new \ReflectionMethod($target[0], $target[1]);
      }

      $targetClass = null;
      if(is_a($target, "ReflectionClass")){
        $targetClass = $target;
        $target = $target->getConstructor();
      }

      if(!is_a($target, "ReflectionMethod"))
        throw new \Exception("Could not inject dependencies for '".$target->getName()."'");

      $injected = array();
      $skip = 0;
      if(is_array($normalArgArray)){
        $injected = array_merge($normalArgArray, $injected);
        $skip = count($normalArgArray);
      }

      $parameters = $target->getParameters();
      foreach($parameters as $parameter) {
        if($skip > 0){
          $skip--;
          continue;
        }

        $injected[] = $this->buildDependency($parameter);
      }

      if($target->isConstructor())
        return $targetClass->newInstanceArgs($injected);

      return $target->invokeArgs($instance, $injected);
    }

    private function buildDependency(\ReflectionParameter $parameter){
      $type = ucwords($parameter->getName());

      if($this->providers[$type] === null)
        throw new \Exception("No dependency provider found for type '".$parameter->getName()."'");

      $provider = $this->providers[$type];

      return call(array($provider, "getInstance"));
    }
  }