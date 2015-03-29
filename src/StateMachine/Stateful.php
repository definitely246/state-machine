<?php namespace StateMachine;

use ReflectionClass;
use ReflectionMethod;
use StateMachine\Exceptions\InvalidEventTriggered;

trait Stateful
{
	/**
	 * [$_statefulBootstrapped description]
	 * @var boolean
	 */
	private $_statefulBootstrapped = false;

	/**
	 * [$_statefulEvents description]
	 * @var array
	 */
	private $_statefulEvents = array();

	/**
	 * [bootstrapStateMachine description]
	 * @return [type] [description]
	 */
	protected function bootstrapStateMachine()
	{
		if ($this->_statefulBootstrapped) return;

		if (!$this->state) return;

		$class = new ReflectionClass($this->state);

		$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

		foreach ($methods as $method)
		{
			$this->_statefulEvents[] = $method->name;
		}

		$this->state = new $this->state;
	}

	/**
	 * Triggers the method on the current state
	 *
	 * @param  [type] $method [description]
	 * @param  [type] $args   [description]
	 * @return [type]         [description]
	 */
	protected function triggerStateEvent($method, $args)
	{
		$this->bootstrapStateMachine();

		if ($this->state && in_array($method, $this->_statefulEvents))
		{
			return call_user_func_array(array($this->state, $method), $args);
		}

		$methodName = is_string($method) ? $method : 'object';

		throw InvalidEventTriggered("State event not valid: [{$methodName}]");
	}

	/**
	 * Calls the trigger method for us
	 * @param  [type] $method [description]
	 * @param  [type] $args   [description]
	 * @return [type]         [description]
	 */
	public function __call($method, $args)
	{
		try { return $this->triggerStateEvent($method, $args); }

		catch (InvalidEventTriggered $e) { }

		trigger_error('Call to undefined method '.__CLASS__.'::'.$method.'()', E_USER_ERROR);
	}
}