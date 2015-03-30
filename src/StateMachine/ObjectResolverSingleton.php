<?php namespace StateMachine;

class ObjectResolverSingleton
{
	/**
	 * An singleton instance of the object resolver
	 *
	 * @var ObjectResolver
	 */
	static public $instance = null;

	/**
	 * Calls our methods statically on a
	 * singleton instance of this class
	 *
	 * @param  string $name
	 * @param  array  $args
	 * @return mixed
	 */
	static public function __callStatic($name, $args)
	{
		static::$instance = static::$instance ?: new ObjectResolver;

		return call_user_func_array(array(static::$instance, $name), $args);
	}
}