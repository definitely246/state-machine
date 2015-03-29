<?php namespace StateMachine\Exceptions;

class StateMachineIsStoppedTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_constructs()
	{
		$e = new StateMachineIsStopped('some message');
		$this->assertInstanceOf('Exception', $e);
	}
}