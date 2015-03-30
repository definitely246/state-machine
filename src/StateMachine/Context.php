<?php namespace StateMachine;

interface Context
{
	/**
	 * Gets the current state
	 *
	 * @return object
	 */
	public function state();

	/**
	 * Sets the current state
	 *
	 * @param   object
	 * @return  void
	 */
	public function setState($state);
}