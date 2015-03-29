# State Machine

## All the things ... erm states

I know we just all adore computer science. It is the most coolest field ever, am I right? I sit here reminiscing about those glory days as a young undergrad computer science major. My favorite thing to do was draw finite state machines on a white board. I mean, Alan Turning be damned, finite state machines are awesome, am I right? ^_^

I wrote this state machine as an example in my [Laravel Design Patterns book](http://www.leanpub.com/larasign). The other ones I found for php were confusing to me. So I didn't want to use them. That might not be a great reason to write your own code, but why do you care? You get to use this awesome open source 100% test covered state machine that I wrote. Okay, enough kidding aside. Use this to create a state machine. Here is how you use it.

## Install

Using composer you can install state machine.

```
composer require definitely246/state-machine
```

## Quickstart Example

```php
class Event1ChangedState1ToState2
{
	public function allow($context)
	{
		return true;
	}
	
	public function handle($context)
	{
		if (!$context->statesChanged) $context->statesChanged = 0;
		print "state1 -> state2\n";
		return $context->statesChanged++;
	}
}

class Event1ChangedState2ToState1
{
	public function allow($context)
   	{
   		return true;
   	}
   	
   	public function handle($context)
   	{
		print "state2 -> state1\n";
   		return $context->statesChanged++;
   	}
}

$transitions = [
	[ 'event' => 'event1', 'from' => 'state1', 'to' => 'state2', 'start' => true],
	[ 'event' => 'event1', 'from' => 'state2', 'to' => 'state1' ],
];

$fsm = new StateMachine\FSM($transitions);

print $fsm->state() . PHP_EOL; // 'state1'

$fsm->event1();	// returns 1, prints 'state1 -> state2'

print $fsm->state() . PHP_EOL; // 'state2'

$fsm->event1();	// 2, prints 'state2 -> state1'

print $fsm->state() . PHP_EOL; // 'state1'
```

## Vending Machine Example

Think about a vending machine that allows you buy candy, snacks, soda pop. If you try to purchase a candy bar without paying for it, the machine shouldn't disperse anything right? We can map out the transisitions for a simple vending machine. Because we are not **evil** we'll add in a refund transition too. This will allow people who inserted their money change their minds and get their money back without purchasing anything.

![a vending machine finite state machine](https://raw.githubusercontent.com/definitely246/state-machine/master/vending-machine-fsm.jpg)

**Note** This particular example is non-deterministic (two outcomes for *purchase* event). If you don't have a problem with it, I got no problem with it either. ^_^

### Transitions

To use `StateMachine` you'll need a list of transistions. Each transistion needs an **event**, **from state** and **to state**.. These 3 things make a transition. Now we convert this diagram into an event table of transitions using the fsm diagram above.

```php
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

```php
$machine = new StateMachine\FSM($transitions);

// throws StateMachine\Exceptions\TransitionHandlerNotFound
```

#### Transition Event Handlers

We have created 5 transitions for this finite state machine. Out of the box every transition requires a handler class. Let's define handler class for our first event `insert` which changes the state from *idle* to *has money*. The class name we need to create is `InsertChangesIdleToHasMoney`. It looks like this.


```php
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

```php
$myCoolerContextObj = new MyCoolerContextObject;
$machine = new StateMachine\FSM($transitions, $myCoolerContextObj);
```

If you are using an Eloquent model (from Laravel), you might do something like this:

```php
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
```

### Object Factory

But wait, that's not all. There is also 3rd parameter on FSM too. In fact, let's show the method signature for the FSM constructor. You can see below that you can apply your own `ObjectFactory` object to finite state machine. This factory is what creates new transition handler objects. If you'd like to change the way handler classes are named, then you should override this factory.

```php
public function __construct($transitions, $context = null, $factory = '')
{
	$this->whiny = true;
	$this->stopped = false;
	$this->context = $context ?: new Context;
	$this->factory = is_string($factory) ? new ObjectFactory($factory, true) : $factory;
	$this->transitions = is_array($transitions) ? new Transitions($transitions) : $transitions;
	$this->state = $this->transitions->startingState();
	$this->addTransitionHandlers();
}
```

If you pass a string to `$factory` it uses that as the namespace for transition event classes.

```php
$context = array();
$machine = new StateMachine\FSM($transitions, $context, '\MyNamespaceToTransitionHandlerEvents');
// throws StateMachine\Exceptions\TransitionHandlerNotFound for \MyNamespaceToTranstitionHandlerEvents\InsertChangesIdleToHasMoney
```

This lets us group our handlers into a single namespace. Now the `StateMachine\Exceptions\TransitionHandlerNotFound` exception should be telling us that it cannot find `\MyNamespaceToTransitionHandlerEvents\InsertChangesIdleToHasMoney`. Neat right? If you need more control, such as turning off `$strictMode` or changing how handler classes are created then you can use your own `ObjectFactory` and provide that factory to your finite state machine constructor.

If you pass `$strictMode = false` to the `ObjectFactory` the anytime transition handler classes are not found, the object factory returns a `StateMachine\DefaultTransitionHandler` instead.

If `ObjectFactory` has `strictMode = true` then you must write a handler for **every** event transition, even if they are just blank. I recommend using `$strictMode = true` because it lets you know quickly which transistion event handler classes you need to create and allows you to tap into the finite state machine's context. 

### Whiny mode

If you don't want exceptions for invalid transition event requests then turn whiny mode off. Note this makes it harder to troubleshoot though.

```php
$fsm->whiny = false;
$fsm->state() 		// 'state1'
$fsm->canPurchase(); 	// returns false
$fsm->purchase();	// returns false (does not throw CannotTransitionForEvent exception)
```

### Canceling State Transition

You can cancel event transitions in the `handle()` method using an exception.

```php
class InsertChangesIdleToHasMoney
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

```php
$fsm->state();	// 'idle'
$fsm->event3();	// ['some' => 'stuff here']
$fsm->state();	// 'idle' <-- not changed to 'has money' state
```

### Triggering Another State Transition

You can also trigger events off another state. This is complex and probably should be avoided. However, if you find yourself needing to trigger an event inside of another event, then you can use `TriggerTransitionEvent`.

```php
class InsertChangesHasMoneyToHasMoney
{
	public function allow($context, $coins)
	{
		return true;	// allows this transition to run
	}

	public function handle($context, $coins)
	{
		// force the vending machine to refund money...
		// this ends up calling $fsm->trigger('refund', []);
		if ($coins < 25) {
			throw new StateMachine\Exceptions\TriggerTransitionEvent('refund', $args = []);
		}
	}
}
```

Now triggering *insert* inside of *has money* state actually ends up triggering *refund*

```php
$fsm->state();	// 'has money'
$fsm->insert(5);
$fsm->state();	// 'idle' <-- the user was refunded
```

### Finite State Machine Stopped

You can see if the fsm is stopped at anytime. Once in stopped all events triggered will fail. If whiney mode is true then you will get a `StateMachineIsStopped` exception, otherwise you'll get a false.

```php
$fsm->state();				// 'has money' state
$fsm->trigger('purchase', ['Pepsi']);	// user bought a pepsi
$fsm->state();				// 'out of stock' state
$fsm->isStopped();			// true
$fsm->insert(125);			// throws StateMachine\StateMachineIsStopped exception
```

## Licence

This is a MIT licence. That means you can pretty much use it for any of your cool projects. 


## Contributions

If you want to make changes, please fork this repository and make a pull request. Ensure that you have unit tests written for any new functionality and they are passing in phpunit. I use mockery for mocking. Also, if you add responsibilities to classes, you might consider new classes. The FSM class is already doing a lot.

```
vendor/bin/phpunit
```

Did you make it this far? You made it to the bottom of the page? Well, dang. You probably did a lot better in your computer science courses than me. Laters, *amigos*. Have a nice day. ^_^
