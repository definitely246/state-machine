<?php namespace StateMachine;

class StatefulTest extends \PHPUnit_Framework_TestCase
{
	public function test_mock_class_uses_stateful()
	{
		$mock = new StatefulMock;
		$this->assertEquals('statemock1 event1 called', $mock->event1());
	}
}

class StatefulMock
{
	use Stateful;

	protected $state = 'StateMachine\StateMock1';
}

class StateMock1
{
	public $var1; protected $var2; private $var3;

	public function event1()
	{
		return 'statemock1 event1 called';
	}

	public function event2(){}

	protected function event3(){}

	private function event4() {}
}
