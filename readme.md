# State Machine

## All the things ... erm states

I know we just all adore computer science. It is the most coolest field ever, am I right? And remeninse about those glory days as a young undergrad computer science major in college, my favorite thing to do was draw finite state machines on a white board. I mean, Alan Turning be damned, finite state machines are awesome, am I right? ^_^

I wrote this state machine as an example in my [Laravel Design Patterns book](http://www.leanpub.com/larasign). The other ones I found for php were confusing to me. So I didn't want to use them. That might not be a great reason to write your own code, but why do you care? You get to use this awesome open source 100% test covered state machine that I wrote. Okay, enough kidding aside. Use this to create a state machine. Here is how you use it.

## Install

Using composer you can install state machine.

```
composer require definitely246/state-machine
```

## Quickstart Example

```
$transitions = [
	[ 'event' => 'event1', 'from' => 'state1', 'to' => 'state2', 'start' => true],
	[ 'event' => 'event2', 'from' => 'state2', 'to' => 'state3' ],
	[ 'event' => 'event3', 'from' => 'state3', 'to' => 'state1' ],
	[ 'event' => 'event4', 'from' => 'state1', 'to' => 'state5', 'stop' => true ],
];

$context = new StdClass;
$factory = new ObjectFactory($namespace = '', $strictMode = false);
$fsm = new StateMachine\FSM($transitions, $context, $factory);

print $fsm->state() . PHP_EOL; // 'state1'

$fsm->event1();

print $fsm->state() . PHP_EOL; // 'state2'

// let's try to trigger an invalid event transition
try {
	$fsm->event3();	// not allowed, there is no transition defined for this
} catch (\StateMachine\Exceptions\CannotTransitionForEvent $e) {

}

// if you don't want exceptions, turn whiny mode off,
$fsm->whiny = false;

$fsm->event3(); // returns false

// not really recommended though, when you can use `canEvent3()`
if ($fsm->canEvent3()) {
	$fsm->state() // 'state2'
}

print $fsm->state() . PHP_EOL; // 'state2'

// let's advance now,

$fsm->event2();
print $fsm->state() . PHP_EOL; // 'state3'

$fsm->event3();
print $fsm->state() . PHP_EOL; // 'state1'

$fsm->event4();
$fsm->isStoppped();	// true
$fsm->event2(); 	// throws StateMachine\Exceptions\StateMachineIsStopped
					// because $fsm->whiny is true
```

Of course, this quick start example doesn't really deal with handlers. It uses the `StateMachine\DefaultHandler` since `$strictMode = false` for our `ObjectFactory`. Let's define an example handler class for this example.

```
class Event3ChangesState3ToState4
{
	public function allow($context, $arg1, $arg2)
	{
		// let's the FSM know if this transition
		// should even be allowed to run, when
		// false, the FSM will check for the next
		// Event3 and State3 transition in the
		// transitions the FSM is constructed with
		return true;
	}

	public function handle($context, $arg1, $arg2)
	{
		// when this event is being called, you can
		// actually do stuff... which makes the FSM
		// a little more interesting

		return "we are handling event3 and state3 -> state4\n";
	}
}
```

Now that we've defined this event transition handler class, we can call our fsm above and see a print message.

```
// assuming we are on state3
$fsm->state(); 						// 'state3'
print $fsm->event3('arg1', 'arg2');	// prints "we are handling event3 and ..."
$fsm->state();						// 'state1'
```

See how the transition handler was executed? This allows us to write handler events for our finite state machine transitions. In fact, if `ObjectFactory` has `strictMode = true` then you must write a handler for **every** event transition, even if they are just blank. I recommend using `$strictMode = true` because it lets you know quickly which transistion event handler classes you need to create and allows you to tap into the finite state machine's context. You can even cancel event transitions in the `handle()` method.

```
class Event3ChangesState3ToState4
{
	public function allow($context)
	{
		return true;	// allows this transition to run
	}

	public function handle($context)
	{
		$response = ['some' => 'stuff here'];
		throw new StateMachine\Exceptions\ShouldNotTransition($response);
	}
}
```
With the above changes to our event transition handler we will get the following output

```
$fsm->state();	// 'state3'
$fsm->event3();	// ['some' => 'stuff here']
$fsm->state();	// 'state3' <-- not changed
```

You can also trigger events off another state. This is complex and probably should be avoided. However, if you find yourself needing to trigger an event inside of another event, then you can use `TriggerTransitionEvent`.

```
class Event1ChangesState1ToState2
{
	public function allow($context)
	{
		return true;	// allows this transition to run
	}

	public function handle($context)
	{
		throw new StateMachine\Exceptions\TriggerTransitionEvent('event4', ['arg1', 'arg2', 'arg3']);
	}
}
```

```
$fsm->state();	// 'state1'
$fsm->event1();
$fsm->state();	// 'state5' <-- the stopped state
$fsm->isStopped();
```

That's a long quickstart. I've included a vending machine example below that I use in my [book](http://www.leanpub.com/larasign) for more details on this state machine.

## Vending Machine Example

Think about a vending machine that allows you buy candy, snacks, soda pop. If you try to purchase a candy bar without paying for it, the machine shouldn't disperse anything right? We can map out the transisitions for a simple vending machine. Because we are not **evil** we'll add in a refund transition too. This will allow people who inserted their money change their minds and get their money back without purchasing anything.

![a vending machine finite state machine](https://raw.githubusercontent.com/definitely246/state-machine/master/vending-machine-fsm.jpg)

**Note** This particular example is non-deterministic (two outcomes for *purchase* event). If you don't have a problem with it, I got no problem with it either. ^_^

### Transitions

To use `StateMachine` you'll need a list of transistions. Each transistion needs an **event**, **from state** and **to state**.. These 3 things make a transition. Now we convert this diagram into an event table of transitions using the fsm diagram above.

```
$transitions = [
	[
		'event' => 'insert',		// inserting money
		'from' 	=> 'idle',			// changes idle state
		'to' 	=> 'has money',		// to has money state
		'start' => true,			// this is starting state
	],
	[
		'event' => 'insert',		// inserting more
		'from' 	=> 'has money',		// money is okay
		'to'   	=> 'has money',		// state does not change
	],
	[
		'event' => 'refund',		// refunding when in
		'from' 	=> 'has money',		// has money state
		'to' 	=> 'idle',			// sets us back to idle
	],
	[
		'event' => 'purchase',		// stops the fsm because
		'from'	=> 'has money',		// all items have been
		'to'	=> 'out of stock',	// purchased and there is
		'stop'  => true,			// no more idle state
	],
	[
    	'event' => 'purchase',		// when we make it to this
	    'from' 	=> 'has money',		// transition, we purchase item.
	    'to' 	=> 'idle',			// order matters, see transition above?
	],
];
```

Take a good long stare at the transitions above. I think we got them all. You can step through them. Now that we have our transitions defined, we need to create a finite state machine that uses these transitions.

```
$machine = new StateMachine\FSM($transitions);

// throws StateMachine\Exceptions\TransitionHandlerNotFound
```

#### Transition Event Handlers

We have created 5 transitions for this finite state machine. Out of the box every transition requires a handler class. Let's define handler class for our first event `insert` which changes the state from *idle* to *has money*. The class name we need to create is `InsertChangesIdleToHasMoney`. It looks like this.


```
class InsertChangesIdleToHasMoney
{
	public function allow($context)
	{
		return true;	// allow this state change
	}

	public function handle($context)
	{
		// do moose stuff here
	}
}
```

### Context

You might be wondering, what is this $context thing? It happens to be a very generic storage object. We can set our own context object on our finite state machine if we'd like. The context is passed to all transition events. It's a way for states to communiate changes with each other. It is the 2nd parameter of the constructor.

```
$myCoolerContextObj = new MyCoolerContextObject;
$machine = new StateMachine\FSM($transitions, $myCoolerContextObj);
```

If you are using an Eloquent model (from Laravel), you might do something like this:

```
class MyModel extends \Eloquent
{
	protected $transitions = [
		...
	];

	public function __construct($attributes = array())
	{
		$this->fsm = new \StateMachine\FSM($this->transitions, $this);
	}
}

### Object Factory

But wait, that's not all. There is also 3rd parameter on FSM too. In fact, let's show the method signature for the FSM constructor. You can see below that you can apply your own `ObjectFactory` object to finite state machine. This factory is what creates new transition handler objects. If you'd like to change the way handler classes are named, then you should override this factory.

```
public function __construct(array $transitions, $context = null, $factory = '')
{
	$this->whiny = true;
	$this->stopped = false;
	$this->context = $context ?: new Context;
	$this->factory = is_string($factory) ? new ObjectFactory($factory, true) : $factory;
	$this->transitions = $this->optimizeTransitions($transitions);
	$this->setInitialState($transitions);
}
```

If you pass a string to `$factory` it uses that as the namespace for transition event classes.

```
$context = array();
$machine = new StateMachine\FSM($transitions, $context, '\MyNamespaceToTransitionHandlerEvents');
// throws StateMachine\Exceptions\TransitionHandlerNotFound
```

This lets us group our handlers into a single namespace. Now the `StateMachine\Exceptions\TransitionHandlerNotFound` exception should be telling us that it cannot find `\MyNamespaceToTransitionHandlerEvents\InsertChangesIdleToHasMoney`. Neat right? If you need more control, such as turning off `$strictMode` or changing how handler classes are created then you can use your own `ObjectFactory` and provide that factory to your finite state machine constructor.


#### Whiny mode

If you don't want exceptions for invalid transition event requests then turn whiny mode off. Note this makes it harder to troubleshoot though.

```
$fsm->whiny = false;
```

## Licence

This is a MIT licence. That means you can pretty much use it for any of your cool projects. 


## Contributions

If you want to make changes, please fork this repository and make a pull request. Ensure that you have unit tests written for any new functionality and they are passing in phpunit. I use mockery for mocking. Also, if you add responsibilities to classes, you might consider new classes. The FSM class is already doing a lot.

```
vendor/bin/phpunit
```

Did you make it this far? You made it to the bottom of the page? Well, dang. You probably did a lot better in your computer science courses than me. Laters, amigos. ^_^
