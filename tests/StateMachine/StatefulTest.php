<?php namespace StateMachine;

class StatefulTest extends \PHPUnit_Framework_TestCase
{
	public function test_it_can_call_magic_method_flipswitch()
	{
		$light = new Light;
		$light->state = 'LightOn';
		$this->assertEquals('light is off', $light->flipSwitch());
		$this->assertEquals('light is on', $light->flipSwitch());
		$this->assertInstanceOf('StateMachine\LightOn', $light->context->state());
	}

	public function test_it_triggers_error_when_magic_method_doesnt_find_event_method()
	{
        $this->setExpectedException('PHPUnit_Framework_Error');
        $light = new Light;
        $light->badMethodName();
	}

	public function test_stateful_classes_must_have_context_property()
	{
        $this->setExpectedException('StateMachine\Exceptions\ContextNotFound');
        $light = new LightWithoutContext;
        $light->flipSwitch();
	}

	public function test_it_throws_exception_if_we_dont_provide_valid_initial_state()
	{
		$this->setExpectedException('StateMachine\Exceptions\StateNotFound');
		$light = new Light;
		$light->state = false;
		$light->flipSwitch();
	}

	public function test_it_can_use_real_class_for_initial_state()
	{
		$light = new Light;
		$light->context = new DefaultContext('StateMachine');
		$light->state = new LightOff($light->context);
		$light->flipSwitch();
	}

	public function test_it_can_handles_invalid_initial_state()
	{
		$this->setExpectedException('StateMachine\Exceptions\InvalidState');
		$light = new Light;
		$light->context = new DefaultContext('StateMachine');
		$light->state = 42; 	// don't allow non-object states
		$light->flipSwitch();
	}

	public function test_it_resolves_string_context_path()
	{
		$light = new Light;
		$light->context = "StateMachine\AnotherLightContext";
		$light->flipSwitch();
		$this->assertInstanceOf('StateMachine\AnotherLightContext', $light->context);
	}

	public function test_it_resolves_string_context_under_this_namespace_path()
	{
		$light = new Light;
		$light->context = "AnotherLightContext";
		$light->flipSwitch();
		$this->assertInstanceOf('StateMachine\AnotherLightContext', $light->context);
	}

	public function test_it_throws_exception_if_context_fails_to_resolve()
	{
		$this->setExpectedException('StateMachine\Exceptions\ContextNotResolvable');
		$light = new Light;
		$light->context = "InvalidLightContext";
		$light->flipSwitch();
		$this->assertInstanceOf('StateMachine\AnotherLightContext', $light->context);
	}

	public function test_it_can_use_this_for_context()
	{
		$light = new LightImplementing1Context;
		$light->context = "this";
		$light->flipSwitch();
		$this->assertInstanceOf('StateMachine\LightImplementing1Context', $light->context);
	}
}


//
// Code below uses the Stateful trait to test behavior
// and correctness
//

class LightOn
{
	public function __construct(Context $context)
	{
		$this->context = $context;
	}

	public function flipSwitch()
	{
		$this->context->setState(new LightOff($this->context));
		$this->context->light = 'off';
		return 'light is off';
	}
}

class LightOff
{
	public function __construct(Context $context)
	{
		$this->context = $context;
	}

	public function flipSwitch()
	{
		$this->context->setState('LightOn');
		$this->context->light = 'on';
		return 'light is on';
	}
}

class Light
{
	use Stateful;

	public $context;

	public $state = 'LightOn';
}

class AnotherLightContext implements Context
{
	public $_state;

	public function state()
	{
		return $this->_state;
	}

	public function setState($state)
	{
		$this->_state = $state;
	}
}

class LightWithoutContext
{
	use Stateful;

	public $state = 'LightOn';
}

class LightImplementing1Context extends AnotherLightContext
{
	use Stateful;

	public $context = 'this';

	public $state = 'LightOn';
}