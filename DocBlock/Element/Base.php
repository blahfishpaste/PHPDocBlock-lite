<?php
namespace DocBlock\Element;

use \Reflector;

abstract class Base
{
    protected $reflectionObject;


    /**
     * Store a reference to this Element's related Reflection instance
     *
     * @param \Reflector $reflectionObject
     */
    public function setReflectionObject(Reflector $reflectionObject)
    {
        $this->reflectionObject = $reflectionObject;
    }

    /**
     * Get a reference to this Element's related Reflection instance
     *
     * @return mixed
     */
    public function getReflectionObject()
    {
        return $this->reflectionObject;
    }
}