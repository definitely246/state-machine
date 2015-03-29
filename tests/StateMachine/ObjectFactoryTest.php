<?php namespace StateMachine;

use Mockery as m;

class ObjectFactoryTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_creates_transition_class_name()
	{
		m::mock('EventChangesState1ToState2');
		$transition = new Transition(['event' => 'event', 'from' => 'state1', 'to' => 'state2']);
		$obj = new ObjectFactory($namespace = '', $strictMode = true);
		$this->assertEquals('\EventChangesState1ToState2', $obj->createTransitionClassName($transition));
	}

	public function test_it_creates_transition_class_name_with_namespace()
	{
		m::mock('MyNamespace\EventChangesState1ToState2');
		$transition = new Transition(['event' => 'event', 'from' => 'state1', 'to' => 'state2']);
		$obj = new ObjectFactory('\MyNamespace', true);
		$this->assertEquals('\MyNamespace\EventChangesState1ToState2', $obj->createTransitionClassName($transition));
	}

	/**
	 * @expectedException StateMachine\Exceptions\TransitionHandlerNotFound
	 */
	public function test_it_throws_exception_in_strict_mode_when_handler_doesnt_exist()
	{
		$transition = new Transition(['event' => 'event', 'from' => 'state2', 'to' => 'state3']);
		$obj = new ObjectFactory('', true);
		$obj->createTransitionClassName($transition);
	}

	public function test_it_skips_exception_when_not_in_strict_mode_and_handler_doesnt_exist()
	{
		$transition = new Transition(['event' => 'event', 'from' => 'state2', 'to' => 'state3']);
		$obj = new ObjectFactory('', false);
		$this->assertEquals('\EventChangesState2ToState3', $obj->createTransitionClassName($transition));
	}

	public function test_it_gives_us_php_class_name()
	{
		$obj = new ObjectFactory('\SomeNameSpace', true);
		$this->assertEquals('SomeEvent', $obj->phpClassName('some event'));
	}

	public function test_it_creates_transition_handler_that_exists()
	{
		$obj = new ObjectFactory('', true);
		$handler = $obj->createTransitionHandler('\StateMachine\ObjectFactoryTest');
		$this->assertInstanceOf('\StateMachine\ObjectFactoryTest', $handler);
	}

	public function test_it_creates_default_transition_handler()
	{
		$obj = new ObjectFactory('', false);
		$handler = $obj->createTransitionHandler('\StateMachine\HandlerObjDoesntExist');
		$this->assertInstanceOf('\StateMachine\DefaultTransitionHandler', $handler);
	}

	/**
	 * @expectedException StateMachine\Exceptions\TransitionHandlerNotFound
	 */
	public function test_it_throws_exception_when_transition_handler_not_found()
	{
		$obj = new ObjectFactory('', true);
		$obj->createTransitionHandler('\StateMachine\HandlerObjDoesntExist');
	}

	public function test_it_makes_new_class()
	{
		$obj = new ObjectFactory('', true);
		$obj->make('\StateMachine\ObjectFactoryTest');
	}
}