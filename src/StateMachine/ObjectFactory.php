<?php namespace StateMachine;

use StateMachine\Exceptions\InvalidPhpClassName;
use StateMachine\Exceptions\TransitionHandlerNotFound;

class ObjectFactory
{
	/**
	 * Namespace where we should create
	 * objects with, leave blank if you want
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Enforces that all transition classes
	 * must be available. Turn this off if
	 * we want to lazily change states
	 *
	 * @var bool
	 */
	protected $strictMode;

	/**
	 * Create a new object factory, this class is intended
	 * to create transition handler objects for the FSM
	 *
	 * @param string  $namespace  [description]
	 * @param boolean $strictMode [description]
	 */
	public function __construct($namespace = '', $strictMode = true)
	{
		$this->namespace = $namespace;
		$this->strictMode = $strictMode;
	}

	/**
	 * Crafts the transition handler class name for us
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  string $event
	 * @return string
	 */
	public function createTransitionClassName($from, $to, $event)
	{
		$fromName = $this->phpClassName($from);

		$toName = $this->phpClassName($to);

		$eventName = $this->phpClassName($event);

		$className = "\\{$eventName}Changes{$fromName}To{$toName}";

		$fqnClassName = $this->namespace . $className;

		if ($this->strictMode && !class_exists($fqnClassName))
		{
			throw new TransitionHandlerNotFound("Could not find class at {$fqnClassName}");
		}

		return $fqnClassName;
	}

	/**
	 * Converts this string into a human/php
	 * readable string. Needs to be a valid
	 * php class name.
	 *
	 * @param  string $name
	 * @return string
	 */
	public function phpClassName($name)
	{
		$name = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));

		if ($this->strictMode && $name !== preg_replace("/[^A-Za-z0-9]/", '', $name))
		{
			throw new InvalidPhpClassName("This is not a valid php class name: $name");
		}

		return preg_replace("/[^A-Za-z0-9 ]/", '', $name);
	}

	/**
	 * Creates the object given a class name
	 *
	 * @param  string $className
	 * @return object
	 */
	public function createTransitionHandler($className)
	{
		if ($this->strictMode && !class_exists($className))
		{
			throw new TransitionHandlerNotFound("Could not find class at {$className}");
		}

		if (!class_exists($className))
		{
			return new DefaultTransition;
		}

		return $this->make($className);
	}

	/**
	 * Makes the object given a class name
	 *
	 * @param  string $className
	 * @return object
	 */
	public function make($className)
	{
		return new $className;
	}
}