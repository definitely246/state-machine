<?php namespace StateMachine;

class DefaultContextTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_can_get_and_set_values()
	{
		$obj = new DefaultContext;
		$obj->test = 'asdf';
		$this->assertEquals('asdf', $obj->test);
		$this->assertEquals(null, $obj->property_does_not_exist);
	}

	public function test_it_can_modify_state()
	{
		$context = new DefaultContext;
		$context->setState('State1');
		$this->assertEquals('State1', $context->state());
	}
}