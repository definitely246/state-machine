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
	 * @param Transitions|array		$transitions
	 * @param Context 				$context
	 * @param string|ObjectFactory 	$factory
	 */
	public function __construct($transitions, $context = null, $factory = '')
	{
		$this->whiny = true;
		$this->stopped = false;
		$this->context = $context ?: new DefaultContext;
		$this->factory = is_string($factory) ? new ObjectFactory($factory, true) : $factory;
		$this->transitions = is_array($transitions) ? new Transitions($transitions) : $transitions;
		$this->state = $this->transitions->startingState();
		$this->addTransitionHandlers();
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
	 * Allow us to set the state independently of 
	 * the transitions settings (e.g. is we're 
	 * restoring an object)
	 */
	public function setState($state)
    {
        $this->state = $state;
        $this->stopped = in_array($state, $this->transitions->stops());
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

		$handler = $this->factory->createTransitionHandler($transition->handler());

		try
		{
			$results = call_user_func_array(array($handler, 'handle'), $this->params($args));

			$this->state = $transition->to();

			$this->stopped = $transition->stop();

			return $results;
		}
		catch (ShouldNotTransition $e)
		{
			return $e->getResults();
		}
		catch (TriggerTransitionEvent $e)
		{
			$this->state = $transition->to();

			$this->stopped = $transition->stop();

			return $this->trigger($e->getEvent(), $e->getArgs());
		}

		return $this->whineAboutInvalidTransition($event);
	}

	/**
	 * This magic megic is just icing on the cake
	 * so we can call our events directly on the
	 * FSM instead of using the trigger method
	 *
	 * @codeCoverageIgnore
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
	 * [checkTransitionHandlers description]
	 * @return [type] [description]
	 */
	protected function addTransitionHandlers()
	{
		foreach ($this->transitions as $transition)
		{
			$transition->setHandler($this->factory->createTransitionClassName($transition));
		}
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
		$events = $this->transitions->events();

		$search = strtolower($search);

		foreach ($events as $event)
		{
			$match = strtolower(str_replace([' ', '-', '_'], '', $event));
			if ($search === $match) return $event;
		}

		return false;
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

		$transitions = $this->transitions->findTransitionsForEventAndState($event, $this->state);

		foreach ($transitions as $transition)
		{
			$handler = $this->factory->createTransitionHandler($transition->handler());

			$result = call_user_func_array(array($handler, 'allow'), $this->params($args));

			if ($result) return $transition;
		}

		return null;
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
}