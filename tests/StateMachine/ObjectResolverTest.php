<?php namespace StateMachine;

class ObjectResolverTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_gets_fully_qualified_namespace_for_real_class()
	{
		$resolver = new ObjectResolver;
		$this->assertEquals('StateMachine', $resolver->fullyQualifiedNamespace($this));
	}

	public function test_it_gets_fully_qualified_namespace_for_class_as_string()
	{
		$resolver = new ObjectResolver;
		$this->assertEquals('StateMachine', $resolver->fullyQualifiedNamespace('StateMachine\ObjectResolverTest'));
	}

	public function test_fully_qualified_namespace_returns_false_for_invalid_class()
	{
		$resolver = new ObjectResolver;
		$this->assertFalse($resolver->methods('BADCLASSYO!'));
	}

	public function test_it_doesnt_try_to_resolve_invalid_class()
	{
		$resolver = new ObjectResolver;
		$this->assertFalse($resolver->methods(1));	// 1 is not a class...
	}

	public function test_it_gets_methods_for_real_class()
	{
		$resolver = new ObjectResolver;
		$mock = new ObjectResolverTestMock;
		$this->assertCount(2, $resolver->methods($mock));
	}

	public function test_it_gets_methods_for_class_as_string()
	{
		$resolver = new ObjectResolver;
		$methods = $resolver->methods('StateMachine\ObjectResolverTestMock');
		$this->assertCount(2, $methods);
	}

	public function test_it_gets_private_methods_for_class()
	{
		$resolver = new ObjectResolver;
		$methods = $resolver->methods('StateMachine\ObjectResolverTestMock', 'private');
		$this->assertCount(1, $methods);
	}

	public function test_it_gets_protected_methods_for_class()
	{
		$resolver = new ObjectResolver;
		$methods = $resolver->methods('StateMachine\ObjectResolverTestMock', 'protected');
		$this->assertCount(1, $methods);
	}

	public function test_it_gets_all_methods_for_class()
	{
		$resolver = new ObjectResolver;
		$methods = $resolver->methods('StateMachine\ObjectResolverTestMock', null);
		$this->assertCount(4, $methods);
	}

	public function test_it_gets_filtered_methods()
	{
		$resolver = new ObjectResolver;
		$this->assertCount(1, $resolver->methods(new ObjectResolverTestMock, 'protected'));
	}

	public function test_methods_returns_false_for_invalid_class()
	{
		$resolver = new ObjectResolver;
		$this->assertFalse($resolver->methods('BADCLASSYO!'));
	}

	public function test_methods_without_magic()
	{
		$resolver = new ObjectResolver;
		$this->assertCount(3, $resolver->methodsWithoutMagic(new ObjectResolverTestMock, array('public', 'private', 'protected')));
	}
}


class ObjectResolverTestMock
{
	public function doMooseStuff() {}
	public function __get($key) {}
	protected function some_protected_method() {}
	private function some_private_method() {}
}