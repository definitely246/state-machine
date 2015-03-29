<?php namespace StateMachine;

class DefaultTransitionTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_has_default_allow()
	{
		$obj = new DefaultTransition;
		$this->assertTrue($obj->allow());
	}

	public function test_it_has_default_handle()
	{
		$obj = new DefaultTransition;
		$obj->handle();
	}
}