<?php namespace StateMachine;

class DefaultTransition
{
	/**
	 * The default transition allows all states
	 * that have a transition defined
	 *
	 * @return boolean
	 */
	public function allow()
	{
		return true;
	}

	/**
	 * The default transition does nothing
	 * for handle method.
	 */
	public function handle()
	{

	}
}