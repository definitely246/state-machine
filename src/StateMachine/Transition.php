<?php namespace StateMachine;

class Transition
{
	/**
	 * Transition event name
	 * @var string
	 */
	protected $event;

	/**
	 * The state we transition from
	 * @var string
	 */
	protected $from;

	/**
	 * The state we transition to
	 * @var string
	 */
	protected $to;

	/**
	 * Is this the starting transition
	 * @var boolean
	 */
	protected $start;

	/**
	 * Should this transition stop the fsm?
	 * @var boolean
	 */
	protected $stop;

	/**
	 * Class that handles this transition
	 * @var string
	 */
	protected $handler;

	/**
	 * [__construct description]
	 * @param array $transition [description]
	 */
	public function __construct(array $transition = array())
	{
		$this->event = $this->forceExtractFrom($transition, 'event');
		$this->from = $this->forceExtractFrom($transition, 'from');
		$this->to = $this->forceExtractFrom($transition, 'to');
		$this->handler = $this->extractFrom($transition, 'handler', '');
		$this->start = $this->extractFrom($transition, 'start', false);
		$this->stop = $this->extractFrom($transition, 'stop', false);
	}

	/**
	 * Accessor for event
	 *
	 * @return string
	 */
	public function event()
	{
		return $this->event;
	}

	/**
	 * Accessor for to
	 *
	 * @return string
	 */
	public function to()
	{
		return $this->to;
	}

	/**
	 * Accessor for from
	 *
	 * @return string
	 */
	public function from()
	{
		return $this->from;
	}

	/**
	 * Accessor for start
	 *
	 * @return boolean
	 */
	public function start()
	{
		return $this->start;
	}

	/**
	 * Accessor for stop
	 *
	 * @return boolean
	 */
	public function stop()
	{
		return $this->stop;
	}

	/**
	 * Accessor for handler
	 *
	 * @return string
	 */
	public function handler()
	{
		return $this->handler;
	}

	/**
	 * Changes the handler for this transition
	 *
	 * @param string $handler
	 */
	public function setHandler($handler)
	{
		$this->handler = $handler;
	}

	/**
	 * Helper method so we don't have to junk up our
	 * code with a ton of array_key_exists
	 *
	 * @param  array  $transition
	 * @param  string $attribute
	 * @param  mixed  $default
	 * @return mixed
	 */
	protected function extractFrom($transition, $attribute, $default = null)
	{
		return array_key_exists($attribute, $transition)
			? $transition[$attribute]
			: $default;
	}

	/**
	 * Helper method so we don't have to
	 * do a bunch of array key exists
	 *
	 * @param  array  $transition
	 * @param  string $attribute
	 * @return mixed
	 */
	protected function forceExtractFrom($transition, $attribute)
	{
		if (!array_key_exists($attribute, $transition))
		{
			throw new \InvalidTransition("Transition must have {$attribute}");
		}

		return $transition[$attribute];
	}
}