<?php namespace StateMachine\Exceptions;

class ShouldNotTransition extends \Exception
{
	/**
	 * Results that will be returned to catcher
	 *
	 * @var mixed
	 */
	protected $results;

	/**
	 * Creates a new exception
	 *
	 * @param mixed $results
	 */
	public function __construct($results)
	{
		parent::__construct("Should not transition to next state!");

		$this->results = $results;
	}

	/**
	 * Get the results from this exception
	 *
	 * @return mixed
	 */
	public function getResults()
	{
		return $this->results;
	}
}