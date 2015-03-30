<?php namespace StateMachine;

use StateMachine\Exceptions\InvalidState;
use StateMachine\Exceptions\StateNotResolvable;
use StateMachine\Exceptions\StateNotFound;
use StateMachine\Exceptions\ContextNotResolvable;
use StateMachine\Exceptions\ContextNotFound;

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
	 * @codeCoverageIgnore
	 * @param  string $method
	 * @param  array $args
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		list ($success, $results) = $this->statefulTrigger($method, $args);

		if (!$success)
		{
			trigger_error('Call to undefined method '.__CLASS__.'::'.$method.'()', E_USER_ERROR);
		}

		return $results;
	}

	/**
	 * Override this to do moose stuff
	 * @return boolean
	 */
	protected function statefulStarting()
	{
		// override this to do moose stuff
		$shouldStart = true;

		return $shouldStart;
	}

	/**
	 * Override thsi method to do moose stuff
	 * @return void
	 */
	protected function statefulStarted()
	{
		// override this to do moose stuff
	}

	/**
	 * Initializes the state machine
	 *
	 * @return void
	 */
	protected function statefulStartStateMachine()
	{
		if ($this->stateful_is_bootstrapped) return;

		if (!$this->state) {
			throw new StateNotFound("Could not find valid \$state on this stateful object");
		}

		if (!property_exists($this, 'context')) {
			throw new ContextNotFound("Could not find \$context property on this stateful object");
		}

		if (!$this->statefulStarting()) return;

		$this->stateful_is_bootstrapped = true;

		$this->stateful_namespace = $this->statefulNamespace($this->state);

		$this->context = $this->statefulContextObject($this->context);

		$this->state = $this->statefulCreateStateObject($this->state);

		$this->stateful_events = $this->statefulEvents($this->state);

		$this->context->setState($this->state);

		$this->statefulStarted();
	}

	/**
	 * A context object is used to initialize
	 * all states
	 *
	 * @return object
	 */
	protected function statefulContextObject($context)
	{
		if (is_null($context))
		{
			return new DefaultContext($this->stateful_namespace);
		}

		if (is_object($context))
		{
			return $context;
		}

		if ($context === 'this')
		{
			return $this;
		}

		if (is_string($context) && $obj = ObjectResolverSingleton::make($context))
		{
			return $obj;
		}

		$fqn = ObjectResolverSingleton::fullyQualifiedNamespace($this);

		if (is_string($context) && $obj = ObjectResolverSingleton::make("{$fqn}\\{$context}"))
		{
			return $obj;
		}

		throw new ContextNotResolvable("Could not resolve $context");
	}


	/**
	 * Gets the namespace for this object or class
	 *
	 * @param  string|object $objOrClass
	 * @return string
	 */
	protected function statefulNamespace($classOrObject)
	{
		if (is_object($classOrObject) || class_exists($classOrObject))
		{
			return ObjectResolverSingleton::fullyQualifiedNamespace($classOrObject);
		}

		$thisNamespace = ObjectResolverSingleton::fullyQualifiedNamespace($this);

		$stateNamespace = ObjectResolverSingleton::fullyQualifiedNamespace("{$thisNamespace}\\{$classOrObject}");

		if ($stateNamespace !== false)
		{
			return $stateNamespace;
		}

		throw new InvalidState("Could not find state: [{$classOrObject}]");
	}

	/**
	 * Creates the state object for us
	 *
	 * @param  object|string $state
	 * @return object
	 */
	protected function statefulCreateStateObject($state)
	{
		if (is_object($state))
		{
			return $state;
		}

		$class = $this->stateful_namespace . '\\' . $state;

		$newState = ObjectResolverSingleton::make($class, $this->context);

		$newState = $newState ?: ObjectResolverSingleton::make($state, $this->context);

		if (!$newState) throw new StateNotResolvable("Could not resolve state, attempted: [{$class}, {$state}]");

		return $newState;
	}

	/**
	 * Returns a list of events on this state object
	 *
	 * @param  Object $state
	 * @return array
	 */
	protected function statefulEvents($state)
	{
		$events = ObjectResolverSingleton::methodsWithoutMagic($state);

		return $events ?: array();
	}

	/**
	 * Provides a way to trigger events without exception
	 *
	 * @param  string $method
	 * @param  array  $args
	 * @return array(success, results)
	 */
	protected function statefulTrigger($method, $args)
	{
		$this->statefulStartStateMachine();

		if (in_array($method, $this->stateful_events))
		{
			$state = $this->context->state();

			return array(true, call_user_func_array(array($state, $method), $args));
		}

		return array(false, null);
	}
}