<?php namespace StateMachine;

use StateMachine\Stateful;

class StatefulTest extends \PHPUnit_Framework_TestCase
{
	public function test_light_example()
	{
		$light = new Light;
		$this->assertEquals('light is off', $light->flipSwitch());
		$this->assertEquals('light is on', $light->flipSwitch());
	}
}


class LightOn
{
	public function __construct(Light $light)
	{
		$this->light = $light;
	}

	public function flipSwitch()
	{
		$this->light->setState(new LightOff($this->light));

		return 'light is off';
	}
}

class LightOff
{
	public function __construct(Light $light)
	{
		$this->light = $light;
	}

	public function flipSwitch()
	{
		$this->light->setState('LightOn');

		return 'light is on';
	}
}

class Light
{
	use Stateful;

	protected $state = 'StateMachine\LightOn';
}