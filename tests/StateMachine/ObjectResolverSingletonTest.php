<?php namespace StateMachine;

class ObjectResolverSingletonTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_gets_fully_qualified_namespace_for_real_class()
	{
		ObjectResolverSingleton::methods($this);
		$this->assertInstanceOf('StateMachine\ObjectResolver', ObjectResolverSingleton::$instance);
	}
}