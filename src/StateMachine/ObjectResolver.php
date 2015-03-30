<?php namespace StateMachine;

use ReflectionClass;
use ReflectionMethod;

class ObjectResolver
{
	/**
	 * Known magic methods
	 * @var array
	 */
	protected $magicMethods = array('__construct', '__destruct', '__call',
		'__callStatic', '__get', '__set', '__isset', '__unset', '__sleep',
		'__wakeup', '__toString', '__invoke', '__set_state', '__clone',
		'__debugInfo');

	/**
	 * All types of meethods
	 * @var array
	 */
	protected $methodTypes = array(
		'static' => ReflectionMethod::IS_STATIC,
		'public' => ReflectionMethod::IS_PUBLIC,
		'protected' => ReflectionMethod::IS_PROTECTED,
		'private' => ReflectionMethod::IS_PRIVATE,
		'abstract' => ReflectionMethod::IS_ABSTRACT,
		'final' => ReflectionMethod::IS_FINAL
	);

	/**
	 * Returns the full qualified namespace
	 * for this object or class
	 *
	 * @param  string|object $classOrObject
	 * @return boolean(false) | string
	 */
	public function fullyQualifiedNamespace($classOrObject)
	{
		$class = $this->resolveClassOrObject($classOrObject);

		return $class ? $class->getNamespaceName() : false;
	}

	/**
	 * Returns all the public methods for this
	 * class or object
	 *
	 * @param  string|object $classOrObject
	 * @param  array		 $filters
	 * @param  array		 $excludes
	 * @return array|false
	 */
	public function methods($classOrObject, $methodTypes = array('public'), $excludes = array())
	{
		$class = $this->resolveClassOrObject($classOrObject);

		$methods = $this->getMethodsFilteredByType($class, $methodTypes);

		$methods = $this->filterMethodsByExclusion($methods, $excludes);

		$methods = $this->getMethodNamesOnly($methods);

		return $class ? $methods : false;
	}

	/**
	 * Returns all methods without magic
	 *
	 * @param  string|object $classOrObject
	 * @param  array 		 $filters
	 * @return array | false
	 */
	public function methodsWithoutMagic($classOrObject, $filters = array('public'))
	{
		return $this->methods($classOrObject, $filters, $this->magicMethods);
	}

	/**
	 * Call constructor for this class
	 *
	 * @param  [type] $classAsString [description]
	 * @param  [type] $args          [description]
	 * @return [type]                [description]
	 */
	public function make($classAsString, $args = array())
	{
		$class = $this->resolveClassOrObject($classAsString);

		$args = is_array($args) ? $args : array($args);

		return $class ? $class->newInstanceArgs($args) : false;
	}

	/**
	 * Resolves the reflection for this
	 *
	 * @param  [type] $classOrObject [description]
	 * @return [type]                [description]
	 */
	protected function resolveClassOrObject($classOrObject)
	{
		if (is_string($classOrObject) && !class_exists($classOrObject))
		{
			return $this->whineAboutBadClass($classOrObject);
		}

		if (!is_string($classOrObject) && !is_object($classOrObject))
		{
			return $this->whineAboutBadClass($classOrObject);
		}

		return new ReflectionClass($classOrObject);
	}

	/**
	 * Extracts out method names only
	 *
	 * @param  array $methods
	 * @return array
	 */
	protected function getMethodNamesOnly($methods)
	{
		$namesOnly = array();

		foreach ($methods as $method)
		{
			$namesOnly[] = $method->name;
		}

		return $namesOnly;
	}

	/**
	 * Excludes method names
	 *
	 * @return array
	 */
	protected function filterMethodsByExclusion($methods, $excludes)
	{
		$filtered = array();

		foreach ($methods as $method)
		{
			if (!in_array($method->name, $excludes))
			{
				$filtered[] = $method;
			}
		}

		return $filtered;
	}

	/**
	 * Gets all the methods on a class given method types
	 *
	 * @param  [type] $class       [description]
	 * @param  [type] $methodTypes [description]
	 * @return [type]              [description]
	 */
	protected function getMethodsFilteredByType($class, $methodTypes)
	{
		if ($class === false) return array();

		$filter = null;

		$methodTypes = !$methodTypes ? array() : $methodTypes;

		$methodTypes = is_array($methodTypes) ? $methodTypes : array($methodTypes);

		foreach ($methodTypes as $methodType)
		{
			$filter = is_null($filter)
				? $this->methodTypes[$methodType]
				: $filter | $this->methodTypes[$methodType];
		}

		$methods = is_null($filter) ? $class->getMethods() : $class->getMethods($filter);

		return $methods;
	}

	/**
	 * Whines about the class or object
	 *
	 * @param  [type] $classOrObject [description]
	 * @return [type]                [description]
	 */
	protected function whineAboutBadClass($classOrObject)
	{
		return false;
	}
}