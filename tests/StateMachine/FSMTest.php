<?php namespace StateMachine;

use Mockery as m;

class FSMTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_gives_us_a_state()
    {
        $fsm = $this->buildFSM();
        $this->assertEquals('idle', $fsm->state());
    }

    public function test_it_tells_us_when_fsm_is_stopped()
    {
        $fsm = $this->buildFSM();
        $this->assertFalse($fsm->isStopped());
    }

    public function test_it_lets_us_know_if_transition_is_allowed()
    {
        $fsm = $this->buildFSM();
        $this->ObjectFactory->shouldReceive('createTransitionHandler')->andReturnSelf();
        $this->ObjectFactory->shouldReceive('allow')->with($this->Context, 'arg1')->andReturn(true);
        $this->assertTrue($fsm->can('insert', ['arg1']));
    }

    public function test_it_lets_us_know_if_transition_is_not_allowed()
    {
        $fsm = $this->buildFSM();
        $this->ObjectFactory->shouldReceive('createTransitionHandler')->andReturnSelf();
        $this->ObjectFactory->shouldReceive('allow')->with($this->Context, 'arg1', 'arg2')->andReturn(true);
        $this->assertFalse($fsm->cannot('insert', ['arg1', 'arg2']));
    }

    public function test_it_can_trigger_state_changes()
    {
        $fsm = $this->buildFSM();
        $this->ObjectFactory->shouldReceive('createTransitionHandler')->once()->andReturnSelf();
        $this->ObjectFactory->shouldReceive('allow')->once()->with($this->Context, 'arg1')->andReturn(true);
        $this->ObjectFactory->shouldReceive('handle')->once()->with($this->Context, 'arg1')->andReturn('results');
        $this->assertEquals('results', $fsm->trigger('insert', 'arg1'));
        $this->assertEquals('has money', $fsm->state());
    }

    public function test_it_uses_transitions_in_order()
    {
        $fsm = $this->buildFSM();
        $this->ObjectFactory->shouldReceive('createTransitionHandler')->once()->andReturnSelf();
        $this->ObjectFactory->shouldReceive('allow')->once()->with($this->Context, 'arg1')->andReturn(true);
        $this->ObjectFactory->shouldReceive('handle')->once()->with($this->Context, 'arg1')->andReturn('results1');
        $this->ObjectFactory->shouldReceive('allow')->twice()->with($this->Context, 'arg2')->andReturn(false, true);
        $this->ObjectFactory->shouldReceive('handle')->once()->with($this->Context, 'arg2')->andReturn('results2');
        $this->assertEquals('results1', $fsm->insert('arg1'));
        $this->assertEquals('results2', $fsm->purchase('arg2'));
        $this->assertEquals('idle', $fsm->state());
        // this should be 'out of stock' but we called allow() twice
    }

    public function test_handlers_can_stop_transitions()
    {
        $exception = new Exceptions\ShouldNotTransition('results from exception');
        $fsm = $this->buildFSM();
        $this->ObjectFactory->shouldReceive('createTransitionHandler')->once()->andReturnSelf();
        $this->ObjectFactory->shouldReceive('allow')->once()->with($this->Context, 'arg1')->andReturn(true);
        $this->ObjectFactory->shouldReceive('handle')->once()->with($this->Context, 'arg1')->andThrow($exception);
        $this->assertEquals('results from exception', $fsm->trigger('insert', 'arg1'));
        $this->assertEquals('idle', $fsm->state());
    }

    public function test_handlers_can_trigger_other_transitions()
    {
        $exception = new Exceptions\TriggerTransitionEvent('purchase', ['product']);
        $fsm = $this->buildFSM();
        $this->ObjectFactory->shouldReceive('createTransitionHandler')->once()->andReturnSelf();
        $this->ObjectFactory->shouldReceive('allow')->once()->with($this->Context, 'arg1')->andReturn(true);
        $this->ObjectFactory->shouldReceive('handle')->once()->with($this->Context, 'arg1')->andThrow($exception);
        $this->ObjectFactory->shouldReceive('allow')->once()->with($this->Context, 'product')->andReturn(true);
        $this->ObjectFactory->shouldReceive('handle')->once()->with($this->Context, 'product')->andReturn('results from 2nd state');
        $this->assertEquals('results from 2nd state', $fsm->trigger('insert', 'arg1'));
        $this->assertEquals('out of stock', $fsm->state());
    }

    public function test_it_magically_calls_can()
    {
        $fsm = $this->buildFSM();
        $this->ObjectFactory->shouldReceive('createTransitionHandler')->once()->andReturnSelf();
        $this->ObjectFactory->shouldReceive('allow')->once()->with($this->Context, 'arg1')->andReturn(true);
        $this->assertTrue($fsm->canInsert('arg1'));
    }

    public function test_it_magically_calls_cannot()
    {
        $fsm = $this->buildFSM();
        $this->ObjectFactory->shouldReceive('createTransitionHandler')->once()->andReturnSelf();
        $this->ObjectFactory->shouldReceive('allow')->once()->with($this->Context, 'arg1')->andReturn(true);
        $this->assertFalse($fsm->cannotInsert('arg1'));
    }

    public function test_it_magically_calls_trigger()
    {
        $fsm = $this->buildFSM();
        $this->ObjectFactory->shouldReceive('createTransitionHandler')->once()->andReturnSelf();
        $this->ObjectFactory->shouldReceive('allow')->once()->with($this->Context, 'arg1')->andReturn(true);
        $this->ObjectFactory->shouldReceive('handle')->once()->with($this->Context, 'arg1')->andReturn('handle results');
        $this->assertEquals('handle results', $fsm->insert('arg1'));
    }

    public function test_it_doesnt_throw_exceptions_when_fsm_is_not_whiny()
    {
        $fsm = $this->buildFSM();
        $fsm->whiny = false;
        $this->assertFalse($fsm->purchase('product'));
    }

    /**
     * @expectedException StateMachine\Exceptions\CannotTransitionForEvent
     */
    public function test_it_throws_exception_when_fsm_is_whiny()
    {
        $fsm = $this->buildFSM();
        $fsm->whiny = true;
        $fsm->purchase('product');
    }

    // build the fsm we use to test with
    protected function buildFSM($additional = array())
    {
        $transitions = [
            [
                'event' => 'insert',    // inserting money
                'from'  => 'idle',      // changes idle state
                'to'    => 'has money', // to has money state
                'start' => true,        // this is starting state
            ],
            [
                'event' => 'insert',    // inserting more
                'from'  => 'has money', // money is okay
                'to'    => 'has money', // state does not change
            ],
            [
                'event' => 'refund',    // refunding when in
                'from'  => 'has money', // has money state
                'to'    => 'idle',      // sets us back to idle
            ],
            [
                'event' => 'purchase',      // stops the fsm because
                'from'  => 'has money',     // all items have been
                'to'    => 'out of stock',  // purchased and there is
                'stop'  => true,            // no more idle state
            ],
            [
                'event' => 'purchase',  // when we make it to this
                'from'  => 'has money', // transition, we purchase item.
                'to'    => 'idle',      // order matters, see transition above?
            ],
        ];

        $this->ObjectFactory = m::mock('StateMachine\ObjectFactory');
        $this->ObjectFactory->shouldReceive('createTransitionClassName')->andReturn('TransitionHandlerClassName');
        $this->Context = m::mock('StateMachine\Context');

        return new FSM($transitions + $additional, $this->Context, $this->ObjectFactory);
    }
}