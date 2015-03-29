<?php namespace StateMachine\Exceptions;

class TriggerTransitionEventTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_constructs()
	{
		$e = new TriggerTransitionEvent('event', ['arg1']);
		$this->assertInstanceOf('Exception', $e);
	}

	public function test_it_has_event()
	{
		$e = new TriggerTransitionEvent('event', ['arg1']);
	}

	public function test_it_has_args()
	{
		$e = new TriggerTransitionEvent('event', ['arg1']);
	}
}