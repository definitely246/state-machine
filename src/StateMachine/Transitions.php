<?php namespace StateMachine;

class Transitions extends \ArrayIterator
{
	/**
	 * array of Transition objects
	 * @var array
	 */
	protected $transitions;

	/**
	 * Lookup table for faster lookups
	 * of transitions
	 *
	 * @var array
	 */
	protected $optimized;

	/**
	 * list of all events
	 *
	 * @var array
	 */
	protected $events;

	/**
	 * list of all states
	 *
	 * @var array
	 */
	protected $states;

	/**
	 * the starting state for these transitions
	 *
	 * @var string
	 */
	protected $startingState;

	/**
	 * These transitions are stored in this class.
	 * This class provides lookup capabilities
	 * for the FSM to use.
	 *
	 * @param array $transitions
	 */
	public function __construct(array $transitions = array())
	{
		$this->transitions = $this->createTransitions($transitions);
		$this->optimized = $this->optimizeTransitions();
		$this->events = $this->findAllEvents();
		$this->states = $this->findAllStates();
		$this->startingState = $this->findStartingState();

		parent::__construct($this->transitions);
	}


	/**
	 * Lists all the events
	 *
	 * @return array
	 */
	public function events()
	{
		return $this->events;
	}

	/**
	 * Lists all the states
	 *
	 * @return array
	 */
	public function states()
	{
		return $this->states;
	}

	/**
	 * Gets the starting state from our transitions
	 * array
	 *
	 * @return string
	 */
	public function startingState()
	{
		return $this->startingState;
	}

	/**
	 * Finds the transitions related to this event and state
	 *
	 * @param  string $event
	 * @return array
	 */
	public function findTransitionsForEventAndState($event, $fromState)
	{
		$transitions = array();

		if (!isset($this->optimized[$event][$fromState]))
		{
			return $transitions;
		}

		foreach ($this->optimized[$event][$fromState] as $index)
		{
			$transitions[] = $this->transitions[$index];
		}

		return $transitions;
	}

	/**
	 * Creates the transitions for this array
	 *
	 * @param  array $transitions
	 * @return array
	 */
	protected function createTransitions(array $items)
	{
		$transitions = array();

		foreach ($items as $item)
		{
			$transitions[] = new Transition($item);
		}

		return $transitions;
	}

	/**
	 * Gets all the events from our transitions
	 *
	 * @return array
	 */
	protected function findAllEvents()
	{
		$events = [];

		foreach ($this->transitions as $transition)
		{
			$events[$transition->event()] = 1;
		}

		return array_keys($events);
	}

	/**
	 * Gets all the states from our transitions
	 *
	 * @return array
	 */
	protected function findAllStates()
	{
		$states = [];

		foreach ($this->transitions as $transition)
		{
			$states[$transition->to()] = 1;
			$states[$transition->from()] = 1;
		}

		return array_keys($states);
	}

	protected function findStartingState()
	{
		$initialState = count($this->transitions) > 0 ? $this->transitions[0]->from() : null;

		foreach ($this->transitions as $transition)
		{
			if ($transition->start()) $initialState = $transition->from();
		}

		return $initialState;
	}

	/**
	 * Optimizes lookup so we can search faster.
	 *
	 * @return array
	 */
	protected function optimizeTransitions()
	{
		$optimized = array();

		foreach ($this->transitions as $index => $transition)
		{
			$event = $transition->event();

			$state = $transition->from();

			if (!isset($optimized[$event])) $optimized[$event] = array();

			if (!isset($optimized[$event][$state])) $optimized[$event][$state] = array();

			$optimized[$event][$state][] = $index;
		}

		return $optimized;
	}

}