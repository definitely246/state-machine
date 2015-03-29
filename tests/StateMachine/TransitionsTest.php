<?php namespace StateMachine;

class TransitionsTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_gives_us_events()
	{
		$transitions = $this->transitions();
		$this->assertCount(2, $transitions->events());
	}

	public function test_it_gives_us_states()
	{
		$transitions = $this->transitions();
		$this->assertCount(3, $transitions->states());
	}

	public function test_it_gives_us_first_transition_as_starting_state()
	{
		$transitions = $this->transitions();
		$this->assertEquals('state1', $transitions->startingState());
	}

	public function test_it_gives_us_selected_transition_as_starting_state()
	{
		$transitions = $this->transitions([['event' => 'event3', 'from' => 'state2', 'to' => 'state1', 'start' => true]]);
		$this->assertEquals('state2', $transitions->startingState());
	}

	public function test_it_finds_transitions_for_event_and_state()
	{
		$transitions = $this->transitions();
		$this->assertCount(1, $transitions->findTransitionsForEventAndState('event2', 'state2'));
	}

	protected function transitions($additional = array())
	{
		$merged = array_merge([
			['event' => 'event1', 'from' => 'state1', 'to' => 'state2'],
			['event' => 'event2', 'from' => 'state2', 'to' => 'state3'],
			['event' => 'event1', 'from' => 'state3', 'to' => 'state1'],
		], $additional);

		return new Transitions($merged);
	}
}
