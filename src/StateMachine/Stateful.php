<?php namespace StateMachine;

use ReflectionClass;
use ReflectionMethod;
use StateMachine\Exceptions\InvalidEventTriggered;

trait Stateful
{
	/**
	 * We only bootstrap our state machine once
	 * @var boolean
	 */
	protected $stateful_is_bootstrapped = false;

	/**
	 * list of events on this state machine
	 * @var array
	 */
	protected $stateful_events = array();

	/**
	 * the namespace for state events
	 * @var string
	 */
	protected $stateful_namespace = '';

	/**
	 * Calls the trigger method for us
	 *
	 * @param  string $method
	 * @param  array $args
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		try { return $this->triggerStateEvent($method, $args); }

		catch (InvalidEventTriggered $e) { }

		trigger_error('Call to undefined method '.__CLASS__.'::'.$method.'()', E_USER_ERROR);
	}

	/**
	 * Sets the state on this
	 *
	 * @param object|string $state
	 */
	public function setState($state)
	{
		$this->state = $this->createStateObject($state);
	}

	/**
	 * Initializes the state machine
	 *
	 * @return void
	 */
	protected function bootstrapStateMachine()
	{
		if ($this->stateful_is_bootstrapped) return;

		if (!$this->state) return;

		$this->stateful_is_bootstrapped = true;

		$class = new ReflectionClass($this->state);

		$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

		$magicMethods = array('__construct', '__destruct', '__call', '__callStatic', '__get', '__set', '__isset', '__unset', '__sleep', '__wakeup', '__toString', '__invoke', '__set_state', '__clone', '__debugInfo');

		foreach ($methods as $method)
		{
			if (!in_array($method->name, $magicMethods))
			{
				$this->stateful_events[] = $method->name;
			}
		}

		$this->stateful_namespace = $class->getNamespaceName();

		$this->state = $this->createStateObject($this->state);
	}

	/**
	 * Creates the state object for us
	 *
	 * @param  object|string $state
	 * @return object
	 */
	protected function createStateObject($state)
	{
		if (!is_string($state))
		{
			return $state;
		}

		$fqn = $this->stateful_namespace . '\\' . $state;

		if (class_exists($fqn))
		{
			return new $fqn($this);
		}

		return new $state($this);
	}

	/**
	 * Triggers the method on the current state
	 *
	 * @param  string $method
	 * @param  array $args
	 * @return mixed
	 */
	protected function triggerStateEvent($method, $args)
	{
		$this->bootstrapStateMachine();

		if ($this->state && in_array($method, $this->stateful_events))
		{
			return call_user_func_array(array($this->state, $method), $args);
		}

		$methodName = is_string($method) ? $method : 'object';

		throw new InvalidEventTriggered("State event not valid: [{$methodName}]");
	}
}