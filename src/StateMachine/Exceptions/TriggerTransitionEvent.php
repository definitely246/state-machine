<?php namespace StateMachine\Exceptions;

class TriggerTransitionEvent extends \Exception
{
	/**
	 * Event name we should trigger
	 * @var string
	 */
	protected $event;

	/**
	 * Arguments we should pass to the next
	 * state
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * This allows us to trigger other states
	 * inside of our handlers
	 *
	 * @param string $event
	 * @param array $args
	 */
	public function __construct($event, array $args = array())
	{
		parent::__construct("Transitioning to another event [{$event}]");

		$this->event = $event;

		$this->args = $args;
	}

	/**
	 * Gets event from this exception
	 *
	 * @return string
	 */
	public function getEvent()
	{
		return $this->event;
	}

	/**
	 * Gets args from this exception
	 *
	 * @return array
	 */
	public function getArgs()
	{
		return $this->args;
	}
}