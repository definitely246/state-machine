<?php namespace StateMachine;

use StateMachine\Exceptions\InvalidState;
use StateMachine\Exceptions\CannotTransitionForEvent;
use StateMachine\Exceptions\ShouldNotTransition;
use StateMachine\Exceptions\TriggerTransitionEvent;
use StateMachine\Exceptions\StateMachineIsStopped;

class FSM
{
	/**
	 * A context is an object that the
	 * finite state machine passes to
	 * every transition allow() and
	 * handle() method. It let's us
	 * communicate between states
	 * if needed.
	 */
	protected $context;

	/**
	 * The factory is what creates objects
	 * for us, this is how we resolve
	 * handlers
	 *
	 * @var ObjectFactory
	 */
	protected $factory;

	/**
	 * Transitions are held here
	 * in an optimized state, so we
	 * can quickly look them up
	 *
	 * @var array
	 */
	protected $transitions;

	/**
	 * If state machine is stopped
	 * then we no longer can do
	 * anything else
	 *
	 * @var bool
	 */
	protected $stopped;

	/**
	 * We keep up with a list of
	 * valid states on this machine
	 * for sanity checking purposes
	 *
	 * @var array
	 */
	protected $states;

	/**
	 * Holds the current state we are on
	 *
	 * @var string
	 */
	protected $state;

	/**
	 * When whiny is set to false we
	 * make the trigger() method return
	 * a false boolean instead of throwing
	 * a nasty ol' mean cold hearted
	 * exception. This defaults to true
	 * because as developers we all love
	 * to whine about stuff. Why should
	 * our classes be any different? ^_^
	 *
	 * @var bool
	 */
	public $whiny;

	/**
	 * Construct the new finite state machine
	 *
	 * @param object 				$context
	 * @param array   				$transitions
	 * @param string|ObjectFactory 	$factory
	 */
	public function __construct(array $transitions, $context = null, $factory = '')
	{
		$this->whiny = true;
		$this->stopped = false;
		$this->context = $context ?: new Context;
		$this->factory = is_string($factory) ? new ObjectFactory($factory, true) : $factory;
		$this->transitions = $this->optimizeTransitions($transitions);
		$this->setInitialState($transitions);
	}

	/**
	 * Gets us the current state of this FSM
	 *
	 * @return string
	 */
	public function state()
	{
		return $this->state;
	}

	/**
	 * Gets us the status of this FSM
	 *
	 * @return boolean
	 */
	public function isStopped()
	{
		return $this->stopped;
	}

	/**
	 * Lets us know if we can proceed with
	 * this event or not.
	 *
	 * @param  string $event
	 * @param  array  $args
	 * @return boolean
	 */
	public function can($event, $args = array())
	{
		return $this->findFirstAllowedTransition($event, $args) !== null;
	}

	/**
	 * Inverse method of can()
	 *
	 * @param  string $event
	 * @param  array  $args
	 * @return boolean
	 */
	public function cannot($event, $args = array())
	{
		return ! $this->can($event, $args);
	}

	/**
	 * Triggers this event on our FSM
	 *
	 * @param  string $event
	 * @return mixed
	 */
	public function trigger($event, $args = array())
	{
		if ($this->stopped)
		{
			return $this->whineAboutStoppedState($event);
		}

		$transition = $this->findFirstAllowedTransition($event, $args);

		if (!$transition)
		{
			return $this->whineAboutInvalidTransition($event);
		}

		$handler = $this->factory->createTransitionHandler($transition['handler']);

		try
		{
			$results = call_user_func_array(array($handler, 'handle'), $this->params($args));

			$this->setState($transition['to']);

			$this->stopped = array_key_exists('stop', $transition) && $transition['stop'];

			return $results;
		}
		catch (ShouldNotTransition $e)
		{
			return $e->getResults();
		}
		catch (TriggerTransitionEvent $e)
		{
			$this->setState($transition['to']);

			$this->stopped = array_key_exists('stop', $transition) && $transition['stop'];

			return $this->trigger($e->getEvent(), $e->getArgs());
		}

		return $this->whineAboutInvalidTransition($event);
	}

	/**
	 * This magic megic is just icing on the cake
	 * so we can call our events directly on the
	 * FSM instead of using the trigger method
	 *
	 * @param  string $event
	 * @param  array $args
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		if (strpos($method, 'cannot') === 0 && $cannotEvent = $this->transformEventBack(substr($method, 6)))
		{
			return $this->cannot($cannotEvent, $args);
		}

		if (strpos($method, 'can') === 0 && $canEvent = $this->transformEventBack(substr($method, 3)))
		{
			return $this->can($canEvent, $args);
		}

		if ($event = $this->transformEventBack($method))
		{
			return $this->trigger($event, $args);
		}

		trigger_error('Call to undefined method '.__CLASS__.'::'.$method.'()', E_USER_ERROR);
	}

	/**
	 * Events can be upper cased or lower cased. They cannot
	 * contain special characters except for '-', '_', and ' '.
	 *
	 * So this means we just need to strip the events of those
	 * special chars, and then match on case insentive
	 *
	 * @param  string $search
	 * @return string
	 */
	protected function transformEventBack($search)
	{
		$events = array_keys($this->transitions);

		$search = strtolower($search);

		foreach ($events as $event)
		{
			$match = strtolower(str_replace([' ', '-', '_'], '', $event));
			if ($search === $match) return $event;
		}

		return false;
	}

	/**
	 * Sets the state for us
	 * @param string $state
	 */
	protected function setState($state)
	{
		if (!in_array($state, $this->states)) {
			throw new InvalidState("State {$state} is not a valid state");
		}

		$this->state = $state;
	}

	/**
	 * Sets the initial state based off of the
	 * transitions array passed in by the user
	 *
	 */
	protected function setInitialState($transitions)
	{
		$initialState = $transitions[0]['from'];

		foreach ($transitions as $transition)
		{
			if (isset($transition['start'])) $initialState = $transition['from'];
		}

		$this->state = $initialState;
	}

	/**
	 * Finds the first allowed transition in our array
	 *
	 * @param  string $event
	 * @return array
	 */
	protected function findFirstAllowedTransition($event, $args)
	{
		if ($this->stopped) return null;

		$transitions = isset($this->transitions[$event][$this->state])
			?  $this->transitions[$event][$this->state]
			: array();

		foreach ($transitions as $transition)
		{
			$handler = $this->factory->createTransitionHandler($transition['handler']);

			$result = call_user_func_array(array($handler, 'allow'), $this->params($args));

			if ($result) return $transition;
		}

		return null;
	}

	/**
	 * Sees if there is a stopped propery for any
	 * transitions that match this state and event
	 *
	 * @param  [type] $event [description]
	 * @param  [type] $state [description]
	 * @return [type]        [description]
	 */
	protected function checkForStopped($event, $state)
	{
		foreach ($this->transitions[$event] as $transitions)
		{
			dd($transition);
			if ($transition['to'] === $state && array_key_exists('stop', $transition) && $transition['stop'])
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the parameters set from the arguments
	 *
	 * @param  array $args
	 * @return mixed
	 */
	protected function params($args)
	{
		$args = is_array($args) ? $args : array($args);

		array_unshift($args, $this->context);


		return $args;
	}

	/**
	 * Whines about stuff man!
	 *
	 * @throws CannotTransitionForEvent if whiny
	 * @return false
	 */
	protected function whineAboutInvalidTransition($event)
	{
		if ($this->whiny === true)
		{
			throw new CannotTransitionForEvent("There are no transitions possible for event: [{$event}]. Current state [{$this->state}]");
		}

		return false;
	}

	/**
	 * Whines about the state being stopped
	 *
	 * @throws  StateMachineIsStopped if whiny
	 * @return false
	 */
	protected function whineAboutStoppedState($event)
	{
		if ($this->whiny === true)
		{
			throw new StateMachineIsStopped("Machine is stopped. You cannot trigger event: [{$event}]");
		}

		return false;
	}

	/**
	 * Reorders the transitions into O(1)
	 * lookup time and also adds handlers
	 * to all these transitions as well
	 *
	 * @return array
	 */
	protected function optimizeTransitions($transitions)
	{
		$optimized = array();

		$states = array();

		foreach ($transitions as $index => $transition)
		{
			if (!isset($transitions[$index]['handler']))
			{
				$transitions[$index]['handler'] = $this->factory->createTransitionClassName($transition['from'], $transition['to'], $transition['event']);
			}

			$states[$transition['to']] = 1;
			$states[$transition['from']] = 1;
		}

		foreach ($transitions as $transition)
		{
			$event = $transition['event'];
			$state = $transition['from'];

			if (!isset($optimized[$event])) {
				$optimized[$event] = array();
			}

			if (!isset($optimized[$event][$state])) {
				$optimized[$event][$state] = array();
			}

			$optimized[$event][$state][] = $transition;
		}

		$this->states = array_keys($states);

		return $optimized;
	}
}