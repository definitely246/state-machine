<?php namespace StateMachine;

class DefaultTransitionHandlerTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_has_default_allow()
	{
		$obj = new DefaultTransitionHandler;
		$this->assertTrue($obj->allow());
	}

	public function test_it_has_default_handle()
	{
		$obj = new DefaultTransitionHandler;
		$obj->handle();
	}
}