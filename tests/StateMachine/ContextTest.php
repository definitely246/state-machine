<?php namespace StateMachine;

class ContextTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_can_get_and_set_values()
	{
		$obj = new Context;
		$obj->test = 'asdf';
		$this->assertEquals('asdf', $obj->test);
		$this->assertEquals(null, $obj->property_does_not_exist);
	}
}