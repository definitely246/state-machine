<?php namespace StateMachine\Exceptions;

class TransitionHandlerNotFoundTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_constructs()
	{
		$e = new TransitionHandlerNotFound('some message');
		$this->assertInstanceOf('Exception', $e);
	}
}