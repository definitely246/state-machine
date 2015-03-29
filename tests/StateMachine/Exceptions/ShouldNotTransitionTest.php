<?php namespace StateMachine\Exceptions;

class ShouldNotTransitionTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_constructs()
	{
		$e = new ShouldNotTransition('results');
		$this->assertInstanceOf('Exception', $e);
	}

	public function test_it_gets_results()
	{
		$e = new ShouldNotTransition('results');
		$this->assertEquals('results', $e->getResults());
	}
}