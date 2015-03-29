<?php namespace StateMachine\Exceptions;

class CannotTransitionForEventTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_constructs()
	{
		$e = new CannotTransitionForEvent('some message');
		$this->assertInstanceOf('Exception', $e);
	}
}