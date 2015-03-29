<?php namespace StateMachine;

class TransitionTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_gets_event()
	{
		$transition = $this->transition();
		$this->assertEquals('event1', $transition->event());
	}

	public function test_it_gets_to()
	{
		$transition = $this->transition();
		$this->assertEquals('state2', $transition->to());
	}

	public function test_it_gets_from()
	{
		$transition = $this->transition();
		$this->assertEquals('state1', $transition->from());
	}

	public function test_it_gets_start()
	{
		$transition = $this->transition();
		$this->assertFalse($transition->start());
	}

	public function test_it_gets_stop()
	{
		$transition = $this->transition();
		$this->assertFalse($transition->stop());
	}

	public function test_it_gets_handler()
	{
		$transition = $this->transition();
		$this->assertEquals('HandlerClass', $transition->handler());
	}

	public function test_it_sets_handler()
	{
		$transition = $this->transition();
		$transition->setHandler('NewHandler');
		$this->assertEquals('NewHandler', $transition->handler());
	}

	private function transition()
	{
		return new Transition(['event' => 'event1', 'from' => 'state1', 'to' => 'state2', 'handler' => 'HandlerClass']);
	}
}