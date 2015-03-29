<?php namespace StateMachine\Exceptions;

class InvalidEventTriggeredTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_constructs()
	{
		$e = new InvalidEventTriggered('some message');
		$this->assertInstanceOf('Exception', $e);
	}
}