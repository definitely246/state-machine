<?php namespace StateMachine\Exceptions;

class InvalidPhpClassNameTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_constructs()
	{
		$e = new InvalidPhpClassName('some message');
		$this->assertInstanceOf('Exception', $e);
	}
}