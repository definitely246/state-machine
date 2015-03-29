<?php namespace StateMachine\Exceptions;

class InvalidStateTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_constructs()
	{
		$e = new InvalidState('some message');
		$this->assertInstanceOf('Exception', $e);
	}
}